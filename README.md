# Paradiso integration for Moodle (local_plmslti)

Adds Paradiso Presentations and Paradiso Roleplay to Moodle as activities.
Teachers pick a presentation or a roleplay from the activity chooser, learners
open it from the course in a new window, and scores go to the Moodle gradebook.

The plugin uses Moodle's own External tool (LTI 1.3) support. It connects your
site to your Paradiso workspace and switches the tools on, so you don't have
to copy keys and URLs between the two systems.

## Requirements

- Moodle 3.9 to 5.3, including Paradiso LMS
- A Paradiso workspace with Presentations, Roleplay, or both
- Your Moodle site must be reachable over https

### Technical requirements

| Component | Requirement |
|---|---|
| Moodle | 3.9 to 5.3 |
| PHP | Whatever your Moodle version needs. The plugin adds nothing on top. |
| Database | Whatever your Moodle version supports (MySQL, MariaDB, PostgreSQL). |
| Plugin type | Local plugin, `local_plmslti` |
| Install location | `<moodleroot>/local/plmslti` (`<moodleroot>/public/local/plmslti` on Moodle 5.1 and later) |
| Moodle settings | External tool (`mod_lti`) is installed. The plugin switches it on during setup. |
| Network | Your Moodle site and Paradiso can reach each other over https. |

### Server permissions for a zip install

Installing from a zip file on the Install plugins page only works when the
user PHP runs as can write to the `local` folder of your Moodle installation.

- The `local` folder must be owned by, or writable by, the PHP user (for
  example `www-data`, `apache`, `nginx` or a PHP-FPM pool user). Use 755 with
  the folder owned by the PHP user. Don't use 777: it isn't needed and it is a
  security risk.
- PHP also needs execute (traverse) access on every parent folder down to the
  Moodle root.
- On servers with SELinux enabled, the `local` folder must allow web server
  writes (`httpd_sys_rw_content_t`).
- `$CFG->disableupdateautodeploy` must not be set to true in `config.php`.

If any of these is missing, Moodle stops with
`[Error] Write access check [<moodleroot>/local]`. Either fix the
permissions or install by hand (see below). To fix the permissions, find the
PHP user:

```sh
ps -eo user,cmd | grep -E 'php-fpm|apache|httpd' | grep -v grep
```

Then give that user the folder:

```sh
sudo chown -R <php-user>:<php-user> <moodleroot>/local
sudo chmod -R 755 <moodleroot>/local
```

If your site is deployed from a code repository, don't change permissions on
the server. Install by hand and add the folder to the repository instead, or
the next deploy removes it.

## Install

### From the Moodle plugins directory

1. Go to Site administration > Plugins > Install plugins.
2. Click **Install plugins from the Moodle plugins directory**, search for
   Paradiso and click **Install now**. Or download the zip from the directory
   and drop it on the Install plugins page.
3. Continue through the screens until Moodle says the install is done. Moodle
   then opens the Connect Paradiso page for you.

### By hand (if Moodle says "Write access check")

Use this when your server does not let Moodle add plugin files itself. Many
hosts lock this on purpose, and it is the usual way on managed or
repository-deployed sites.

1. Unzip the download. It holds one folder, `plmslti`.
2. Upload that folder with SFTP, SSH or your host's file manager to
   `local/plmslti` in your Moodle folder (`public/local/plmslti` on Moodle
   5.1 and later). Check that `local/plmslti/version.php` exists and that the
   PHP user can read the files.
3. Finish the install in one of two ways:
   - Go to Site administration > Notifications and click **Upgrade Moodle
     database now**. Moodle then opens the Connect Paradiso page for you.
   - Or run `php admin/cli/upgrade.php` from the Moodle folder, then open
     `/local/plmslti/index.php` in the browser.

### Check the install

Go to Site administration > Plugins > Plugins overview and check that
**Paradiso integration** (`local_plmslti`) is listed. Its settings and the
Connect Paradiso page are under Site administration > Plugins > Local
plugins > Paradiso integration.

## Connect your Paradiso workspace

1. On the Connect Paradiso page (Site administration > Plugins > Local plugins >
   Paradiso integration > Connect Paradiso), click **Connect with Paradiso**.
2. Sign in as a Paradiso workspace admin and click **Connect Moodle**. New to
   Paradiso? Create your free workspace on the page that opens; you come
   straight back to the same Connect Moodle step afterwards.
3. You come back to Moodle, which registers Presentations and Roleplay and
   switches them on. When the page says Setup finished, you are done.

Got another product later, such as Roleplay? Open Connect Paradiso and click
**Connect again**. Moodle only adds what is new; tools that already work are
left alone.

If your workspace signs in at its own address, enter it as **Paradiso sign-in
address** in the plugin settings first.

### Connect by hand

Open **Connect with a registration link instead** on the Connect Paradiso page.
Copy the tool address and a registration link from the Moodle card under
Integrations in Paradiso. Enter the address as **Paradiso tool address** in
the plugin settings, paste the link and click Register. Repeat for the other
product. These links do not expire. If one leaks, replace it on the Moodle card
and the old one stops working. Sites that are already connected keep working.

## Add an activity

In a course, turn on Edit mode, choose Add an activity or resource, and pick
Paradiso Presentations or Paradiso Roleplay. Click Select content and choose
what learners should see.

## Privacy

The plugin stores no personal data. When a learner opens a Paradiso activity,
Moodle's External tool sends their name and email to Paradiso so the learner
can be signed in and graded.

## Licence

GNU GPL v3 or later. See LICENSE.md.
