<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_plmslti\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * LTI Dynamic Registration for Moodle versions that do not have it.
 *
 * mod_lti gained Dynamic Registration in Moodle 3.10. On older sites this
 * plugin serves the three pieces itself: the start page, the platform
 * configuration and the registration endpoint. Launches, grades and roster
 * still go through mod_lti, which has supported LTI 1.3 since Moodle 3.7.
 *
 * Instead of the signed token mod_lti uses, the start page hands out a random
 * one-time token and keeps only its hash.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registration {
    /** @var int Seconds a registration token stays valid. */
    public const TOKEN_TTL = HOURSECS;

    /** @var string Config name prefix for stored token hashes. */
    private const TOKEN_PREFIX = 'regtoken_';

    /** @var string AGS score scope. */
    private const SCOPE_SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';

    /** @var string AGS line item scope. */
    private const SCOPE_LINEITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';

    /** @var string NRPS scope. */
    private const SCOPE_NRPS = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';

    /** @var string Registration claim holding the LTI tool configuration. */
    private const TOOL_CONFIGURATION = 'https://purl.imsglobal.org/spec/lti-tool-configuration';

    /**
     * Whether mod_lti can do Dynamic Registration itself (Moodle 3.10 and later).
     *
     * @return bool
     */
    public static function core_supported(): bool {
        global $CFG;
        return file_exists($CFG->dirroot . '/mod/lti/startltiadvregistration.php');
    }

    /**
     * The page that starts registering a tool from a registration link.
     *
     * @param string $link Registration link from Paradiso.
     * @return \moodle_url
     */
    public static function start_url(string $link): \moodle_url {
        $page = self::core_supported() ? '/mod/lti/startltiadvregistration.php' : '/local/plmslti/register.php';
        return new \moodle_url($page, ['url' => $link, 'sesskey' => sesskey()]);
    }

    /**
     * Creates a one-time registration token. Expired ones are cleared first.
     *
     * @return string
     */
    public static function new_token(): string {
        $now = time();
        foreach ((array) get_config('local_plmslti') as $name => $expires) {
            if (strpos($name, self::TOKEN_PREFIX) === 0 && (int) $expires < $now) {
                unset_config($name, 'local_plmslti');
            }
        }
        $token = random_string(40);
        set_config(self::TOKEN_PREFIX . hash('sha256', $token), $now + self::TOKEN_TTL, 'local_plmslti');
        return $token;
    }

    /**
     * Uses up a registration token. A token works once, and only before it expires.
     *
     * @param string $token
     * @return bool
     */
    public static function use_token(string $token): bool {
        if ($token === '') {
            return false;
        }
        $name = self::TOKEN_PREFIX . hash('sha256', $token);
        $expires = (int) get_config('local_plmslti', $name);
        if (!$expires) {
            return false;
        }
        unset_config($name, 'local_plmslti');
        return $expires >= time();
    }

    /**
     * This site's LTI platform configuration, as mod_lti publishes it on 3.10 and later.
     *
     * Many Apache and PHP-FPM setups drop the Authorization header before PHP sees it,
     * which leaves the registration endpoint without its Bearer token. So the token also
     * rides on the registration endpoint's address, where openid-registration.php falls
     * back to it. It works once and expires within the hour, like the Bearer token.
     *
     * @param string $token Registration token to put on the registration endpoint, if any.
     * @return array
     */
    public static function platform_configuration(string $token = ''): array {
        global $CFG;
        $registrationendpoint = new \moodle_url('/local/plmslti/openid-registration.php');
        if ($token !== '') {
            $registrationendpoint->param('registration_token', $token);
        }
        return [
            'issuer' => $CFG->wwwroot,
            'token_endpoint' => (new \moodle_url('/mod/lti/token.php'))->out(false),
            'token_endpoint_auth_methods_supported' => ['private_key_jwt'],
            'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
            'jwks_uri' => (new \moodle_url('/mod/lti/certs.php'))->out(false),
            'authorization_endpoint' => (new \moodle_url('/mod/lti/auth.php'))->out(false),
            'registration_endpoint' => $registrationendpoint->out(false),
            'scopes_supported' => ['openid', self::SCOPE_SCORE, self::SCOPE_LINEITEM, self::SCOPE_NRPS],
            'response_types_supported' => ['id_token'],
            'subject_types_supported' => ['public', 'pairwise'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'claims_supported' => ['sub', 'iss', 'name', 'given_name', 'family_name', 'email'],
            'https://purl.imsglobal.org/spec/lti-platform-configuration' => [
                'product_family_code' => 'moodle',
                'version' => $CFG->release,
                'messages_supported' => [
                    ['type' => 'LtiResourceLinkRequest'],
                    ['type' => 'LtiDeepLinkingRequest', 'placements' => ['ContentArea']],
                ],
            ],
        ];
    }

    /**
     * Turns a tool's registration request into mod_lti tool settings.
     *
     * Follows mod_lti's own mapping on 3.10 and later. The tool starts
     * pending; Finish setup activates it.
     *
     * @param array $request Decoded registration request.
     * @param string $clientid Client id for the new tool.
     * @return \stdClass Settings for lti_add_type().
     * @throws \invalid_parameter_exception When a required value is missing or wrong.
     */
    public static function request_to_config(array $request, string $clientid): \stdClass {
        $toolconfig = $request[self::TOOL_CONFIGURATION] ?? null;
        $redirecturis = $request['redirect_uris'] ?? null;
        foreach (['initiate_login_uri', 'client_name', 'jwks_uri'] as $key) {
            if (empty($request[$key]) || !is_string($request[$key])) {
                throw new \invalid_parameter_exception('missing ' . $key);
            }
        }
        if (!is_array($toolconfig) || empty($toolconfig['domain']) || !is_array($redirecturis) || !$redirecturis) {
            throw new \invalid_parameter_exception('missing tool configuration');
        }
        if (!in_array('id_token', (array) ($request['response_types'] ?? []), true)) {
            throw new \invalid_parameter_exception('invalid response_types');
        }
        if (($request['token_endpoint_auth_method'] ?? '') !== 'private_key_jwt') {
            throw new \invalid_parameter_exception('invalid token_endpoint_auth_method');
        }

        $domain = lti_get_domain_from_url(clean_param($toolconfig['domain'], PARAM_TEXT));
        $targetlinkuri = clean_param($toolconfig['target_link_uri'] ?? ('https://' . $domain), PARAM_URL);
        if ($domain !== lti_get_domain_from_url($targetlinkuri)) {
            throw new \invalid_parameter_exception('domain and target_link_uri differ');
        }

        $config = new \stdClass();
        $config->lti_clientid = $clientid;
        $config->lti_typename = clean_param($request['client_name'], PARAM_TEXT);
        $config->lti_toolurl = $targetlinkuri;
        $config->lti_tooldomain = $domain;
        $config->lti_description = clean_param($toolconfig['description'] ?? '', PARAM_TEXT);
        $config->lti_ltiversion = LTI_VERSION_1P3;
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
        $config->lti_icon = clean_param($request['logo_uri'] ?? '', PARAM_URL);
        $config->lti_coursevisible = LTI_COURSEVISIBLE_PRECONFIGURED;
        $config->lti_keytype = LTI_JWK_KEYSET;
        $config->lti_publickeyset = clean_param($request['jwks_uri'], PARAM_URL);
        $config->lti_initiatelogin = clean_param($request['initiate_login_uri'], PARAM_URL);
        $config->lti_redirectionuris = implode(PHP_EOL, clean_param_array($redirecturis, PARAM_URL));
        $config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;
        $config->lti_customparameters = '';

        $config->lti_contentitem = 0;
        foreach ((array) ($toolconfig['messages'] ?? []) as $message) {
            if (is_array($message) && ($message['type'] ?? '') === 'LtiDeepLinkingRequest') {
                $config->lti_contentitem = 1;
                $config->lti_toolurl_ContentItemSelectionRequest = clean_param($message['target_link_uri'] ?? '', PARAM_URL);
            }
        }

        $scopes = explode(' ', (string) ($request['scope'] ?? ''));
        $config->lti_acceptgrades = LTI_SETTING_NEVER;
        $config->ltiservice_gradesynchronization = 0;
        if (in_array(self::SCOPE_SCORE, $scopes, true)) {
            $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $config->ltiservice_gradesynchronization = 1;
        }
        if (in_array(self::SCOPE_LINEITEM, $scopes, true)) {
            $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $config->ltiservice_gradesynchronization = 2;
        }
        $config->ltiservice_memberships = in_array(self::SCOPE_NRPS, $scopes, true) ? 1 : 0;

        $claims = (array) ($toolconfig['claims'] ?? []);
        $config->lti_sendname = array_intersect(['name', 'given_name', 'family_name'], $claims)
            ? LTI_SETTING_ALWAYS : LTI_SETTING_NEVER;
        $config->lti_sendemailaddr = in_array('email', $claims, true) ? LTI_SETTING_ALWAYS : LTI_SETTING_NEVER;
        return $config;
    }

    /**
     * The registration response: what the tool needs to accept launches from this site.
     *
     * @param \stdClass $config Settings the tool was added with.
     * @param int $typeid The new tool's id, which is also its deployment id.
     * @return array
     */
    public static function response(\stdClass $config, int $typeid): array {
        return [
            'client_id' => $config->lti_clientid,
            'response_types' => ['id_token'],
            'jwks_uri' => $config->lti_publickeyset,
            'initiate_login_uri' => $config->lti_initiatelogin,
            'grant_types' => ['client_credentials', 'implicit'],
            'redirect_uris' => explode(PHP_EOL, $config->lti_redirectionuris),
            'application_type' => 'web',
            'token_endpoint_auth_method' => 'private_key_jwt',
            'client_name' => $config->lti_typename,
            'logo_uri' => $config->lti_icon,
            self::TOOL_CONFIGURATION => [
                'version' => LTI_VERSION_1P3,
                'deployment_id' => (string) $typeid,
                'target_link_uri' => $config->lti_toolurl,
                'domain' => $config->lti_tooldomain,
                'description' => $config->lti_description,
            ],
        ];
    }

    /**
     * Adds the tool a registration request describes.
     *
     * @param array $request Decoded registration request.
     * @return array The registration response.
     */
    public static function register(array $request): array {
        $config = self::request_to_config($request, random_string(15));
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_PENDING;
        $typeid = lti_add_type($type, clone $config);
        return self::response($config, $typeid);
    }
}
