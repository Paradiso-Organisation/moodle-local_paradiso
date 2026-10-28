# Paradiso integration for Moodle (local_paradiso)

Adds Paradiso Presentations and Paradiso Roleplay to Moodle as activities.
Teachers pick a presentation or a roleplay from the activity chooser, learners
open it inside the course, and scores go to the Moodle gradebook.

The plugin uses Moodle's own External tool (LTI 1.3) support. It connects your
site to your Paradiso workspace and switches the tools on, so you don't have
to copy keys and URLs between the two systems.

## Requirements

- Moodle 4.5 to 5.3
- A Paradiso workspace with Presentations, Roleplay, or both
- Your Moodle site must be reachable over https

## Install

1. Copy this folder to `local/paradiso` (Moodle 4.5) or `public/local/paradiso`
   (Moodle 5.1 and later), or install the zip from Site administration >
   Plugins > Install plugins.
2. Visit Site administration > Notifications to finish the install.

## Connect your Paradiso workspace

1. Go to Site administration > Plugins > Local plugins > Paradiso integration >
   Settings and enter your Paradiso workspace address, for example
   `https://yourcompany.paradiso.ai`.
2. Open Connect Paradiso on the same menu.
3. In Paradiso, open Integrations, choose Moodle, and copy the link for
   Presentations or Roleplay. Paste it into Registration link and click Register.
4. The page finishes the setup on its own. Repeat for the other tool.

The links do not expire. If one leaks, replace it on the Moodle card in Paradiso
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
