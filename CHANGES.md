# Changes

## 1.0.2 (2026-11-05)

- Connect with Paradiso now goes to https://velocity-dev.paradisolms.net when
  Paradiso sign-in address is left blank. The old default went to an address
  that does not have the Connect page, so Connect failed with a "not found"
  page unless the admin filled in the setting.

## 1.0.1 (2026-11-04)

- Fixes "Registration failed ... invalid_registration_token" on servers whose
  web server drops the Authorization header before PHP sees it (common with
  Apache and PHP-FPM). This hit sites that register through the plugin's own
  pages, such as Moodle 3.9 and Paradiso LMS. The one-time registration token
  now also travels on the registration address, so it gets through either way.
- README: technical requirements, the server permissions a zip install needs,
  and how to finish a manual install from the command line.

## 1.0.0 (2026-11-04)

- First stable release, for the Moodle plugins directory.
- The plugin is now `local_plmslti` and installs to `local/plmslti`. It was
  `local_paradiso` up to 0.2.1. Moodle treats it as a new plugin, so on a
  site that had 0.2.1, uninstall the old one with Remove the tools when
  uninstalling switched off, install this one and click Connect again. The
  tools you already registered keep working.
- After install, Connect Paradiso opens on the first page the admin visits,
  even on sites such as Paradiso LMS that skip Site administration >
  Notifications at the end of an install.
- Works on Moodle 3.9 and later, down from 4.5. Moodle added automatic tool
  registration in 3.10, so on 3.9 the plugin registers the Paradiso tools
  itself. Everything else, such as launches, grades and roster, still runs
  through Moodle's External tool.
- Finish setup also switches on the External tool activity when a site has
  it off. Without it teachers cannot add a Paradiso activity, and on Paradiso
  LMS it does not even appear in the activity picker. The Connect Paradiso
  page and the status check both say when it is off.

## 0.2.1 (2026-11-03)

- Once Paradiso is connected, the Connect Paradiso page says which workspace
  it is connected to, and Connect with Paradiso becomes a secondary Connect
  again button.
- Connect again only registers products that are not connected yet, so it no
  longer adds a second copy of a tool. Use it after your workspace gets
  another product, such as Roleplay.
- Finish setup only shows when a tool still needs it.

## 0.2.0 (2026-11-02)

- Paradiso Presentations and Paradiso Roleplay show their own icons in the
  activity chooser. Click Finish setup on the Connect Paradiso page to add the
  icons to tools you registered with 0.1.0.
- The Paradiso tools now request the Names and Roles service, so Paradiso can
  read the course roster.
- The status check also tests whether Moodle can reach Paradiso, and says how
  to fix it when it cannot.
- Registration tells Paradiso which plugin version is connecting, which helps
  support.
- Users with the Manage the Paradiso integration capability see the Connect
  Paradiso page in the menu.
- Maturity raised to beta.

## 0.1.0 (2026-10-29)

- First release. Connect with Paradiso registers Paradiso Presentations and
  Paradiso Roleplay through LTI Dynamic Registration and switches them on.
- Connect Paradiso opens once, right after the plugin is installed.
- New admins can create a Paradiso account during Connect.
- Connect by hand with a registration link from the Moodle card in Paradiso.
- Status check on the System status report.
- Choose whether uninstalling removes the tools.
