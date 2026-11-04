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

/**
 * Tests for the plugin's own Dynamic Registration, used before Moodle 3.10.
 *
 * @package    local_plmslti
 * @category   test
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_plmslti\local\registration
 */
final class registration_test extends \advanced_testcase {
    /**
     * A registration request shaped like the one Paradiso sends.
     *
     * @return array
     */
    private function paradiso_request(): array {
        return [
            'application_type' => 'web',
            'response_types' => ['id_token'],
            'grant_types' => ['implicit', 'client_credentials'],
            'initiate_login_uri' => 'https://api.paradiso.example/public/pat-lti/login',
            'redirect_uris' => ['https://app.paradiso.example/lti/pat/callback'],
            'client_name' => 'Paradiso Presentations',
            'logo_uri' => 'https://app.paradiso.example/lti-icons/presentations.svg',
            'jwks_uri' => 'https://api.paradiso.example/public/lti/jwks.json',
            'token_endpoint_auth_method' => 'private_key_jwt',
            'scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/score '
                . 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly',
            'https://purl.imsglobal.org/spec/lti-tool-configuration' => [
                'domain' => 'app.paradiso.example',
                'target_link_uri' => 'https://app.paradiso.example/lti/pat/callback',
                'claims' => ['iss', 'sub', 'name', 'email'],
                'messages' => [
                    ['type' => 'LtiResourceLinkRequest'],
                    ['type' => 'LtiDeepLinkingRequest', 'target_link_uri' => 'https://app.paradiso.example/lti/pat/callback'],
                ],
            ],
        ];
    }

    public function test_token_works_once(): void {
        $this->resetAfterTest();
        $token = registration::new_token();
        $this->assertTrue(registration::use_token($token));
        $this->assertFalse(registration::use_token($token));
        $this->assertFalse(registration::use_token(''));
        $this->assertFalse(registration::use_token('made-up'));
    }

    public function test_expired_token_is_rejected_and_cleared(): void {
        $this->resetAfterTest();
        $token = registration::new_token();
        $name = 'regtoken_' . hash('sha256', $token);
        set_config($name, time() - 1, 'local_plmslti');
        registration::new_token();
        $this->assertFalse(get_config('local_plmslti', $name), 'Starting again clears expired tokens.');
        $this->assertFalse(registration::use_token($token));
    }

    public function test_register_adds_a_pending_tool_paradiso_can_launch(): void {
        global $CFG;
        $this->resetAfterTest();
        $response = registration::register($this->paradiso_request());

        $typeid = (int) $response['https://purl.imsglobal.org/spec/lti-tool-configuration']['deployment_id'];
        $type = lti_get_type($typeid);
        $this->assertSame('Paradiso Presentations', $type->name);
        $this->assertSame(LTI_VERSION_1P3, $type->ltiversion);
        $this->assertEquals(LTI_TOOL_STATE_PENDING, $type->state);
        $this->assertSame($type->clientid, $response['client_id']);
        $this->assertSame(tool_setup::KIND_PAT, tool_setup::kind_of($type));

        $config = lti_get_type_config($typeid);
        $this->assertSame(LTI_JWK_KEYSET, $config['keytype']);
        $this->assertSame('https://api.paradiso.example/public/lti/jwks.json', $config['publickeyset']);
        $this->assertSame('https://api.paradiso.example/public/pat-lti/login', $config['initiatelogin']);
        $this->assertSame('https://app.paradiso.example/lti/pat/callback', $config['redirectionuris']);
        $this->assertEquals(1, $config['contentitem']);
        $this->assertEquals(LTI_SETTING_DELEGATE, $config['acceptgrades']);
        $this->assertEquals(1, $config['ltiservice_gradesynchronization']);
        $this->assertEquals(1, $config['ltiservice_memberships']);
        $this->assertEquals(LTI_SETTING_ALWAYS, $config['sendname']);
        $this->assertEquals(LTI_SETTING_ALWAYS, $config['sendemailaddr']);

        $this->assertSame($CFG->wwwroot, registration::platform_configuration()['issuer']);
    }

    public function test_register_rejects_a_request_missing_required_values(): void {
        $this->resetAfterTest();
        $request = $this->paradiso_request();
        unset($request['jwks_uri']);
        $this->expectException(\invalid_parameter_exception::class);
        registration::register($request);
    }

    public function test_register_rejects_a_target_outside_the_tool_domain(): void {
        $this->resetAfterTest();
        $request = $this->paradiso_request();
        $request['https://purl.imsglobal.org/spec/lti-tool-configuration']['target_link_uri'] = 'https://evil.example/x';
        $this->expectException(\invalid_parameter_exception::class);
        registration::register($request);
    }

    public function test_start_url_uses_core_page_when_moodle_has_one(): void {
        $this->resetAfterTest();
        $url = registration::start_url('https://api.paradiso.example/public/lti/register?token=a');
        $expected = registration::core_supported() ? '/mod/lti/startltiadvregistration.php' : '/local/plmslti/register.php';
        $this->assertStringEndsWith($expected, $url->get_path(false));
        $this->assertSame('https://api.paradiso.example/public/lti/register?token=a', $url->get_param('url'));
    }
}
