[![Build Status](https://travis-ci.com/DiscipleTools/disciple-tools-echo.svg?branch=master)](https://travis-ci.com/DiscipleTools/disciple-tools-echo)

# Disciple Tools - Echo

Integrate Echo conversations with Disciple Tools and capture key contact information based on pre-determined outcomes.

## Purpose

This plugin further aids the seeker conversion process, by flagging and creating D.T. contact records based on mapped conversational outcomes.


## Usage

#### Will Do

- Directional updates - Therefore, only accept Echo updates; or just push D.T. updates; or temporarily disable update runs in both directions.
- Cherry-pick Echo conversation outcome options to be processed.
- Specify Echo referrer channels to be processed.
- Map D.T. seeker path options to Echo conversation outcomes.
- Display detailed logging information, to support troubleshooting.

#### Will Not Do

- Does not currently process any other metadata; such as general client activity reporting.

## Requirements

- Disciple Tools Theme installed on a Wordpress Server.
- A live Echo platform, with an active account and API token.

## Installing

- Install as a standard Disciple.Tools/Wordpress plugin in the system Admin/Plugins area.
- Requires the user role of Administrator.

## Setup

- Install the plugin. (You must be an administrator)
- Activate the plugin.
- Navigate to the Extensions (D.T) > Echo menu item in the admin area.
- Enter Echo API token.
- Enter Echo platform host url.
- Disable update flags in both directions until the setup process has been completed.
- Save changes.
- Select and add Echo conversation outcomes which are to be processed. E.g. Requested Face to Face.
- Select and add Echo referrer channels which are to be listened to for incoming conversations.
- Next, create mappings between D.T. seeker path options and Echo conversation outcomes. When a D.T. contact record's seeker path is changed, the corresponding mapped Echo outcome will also be updated.
- Save mapped options and outcomes.
- Enable update flags in both directions and save.
- Finally, have the Echo plugin take it from there! :)

## Contribution

Contributions welcome. You can report issues and bugs in the
[Issues](https://github.com/DiscipleTools/disciple-tools-echo/issues) section of the repo. You can present ideas
in the [Discussions](https://github.com/DiscipleTools/disciple-tools-echo/discussions) section of the repo. And
code contributions are welcome using the [Pull Request](https://github.com/DiscipleTools/disciple-tools-echo/pulls)
system for git. For a more details on contribution see the
[contribution guidelines](https://github.com/DiscipleTools/disciple-tools-echo/blob/master/CONTRIBUTING.md).
