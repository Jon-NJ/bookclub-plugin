# Bookclub Plugin for Wordpress

This is user documentation for using the bookclub plugin. There is a separate developer document.

## Introduction
This software is a plugin for WordPress. The plugin is being developed by Jon Wolfe for use with our book club group in Berlin. It is originally based on a standalone PHP application which has since been retired. It is being offered as [FOSS](https://en.wikipedia.org/wiki/Free_and_open-source_software) under the [MIT license](https://opensource.org/licenses/MIT).

The software provides the following features:

* The are several shortcodes which can display information about the books and their authors that have been or will be read by a book club.
* Events can be created and participants can be invited by email. They can RSVP to these events. A maximum number of users can be set where a waiting list is created when that number is reached.
* When someone de-RSVP's, an email will be sent to the next one on the list to notify them that they have a seat.
* There are features to manage WordPress (and bookclub) members. New members can be sent a signup email which will let them create a WordPress account on the site (with subscriber only priviledges).
* Bookclub members have a profile page where they can configure their information.
* An additional "role" *(Bookclub Admin)*  allows access to all menu items except for **Settings**. It inherits from the role "Editor". 
* Administrators have an extensive set of menu pages for managing the books and members.
* An email forwarder can be activated so that members of the groups can communicate with each other.
* A REST based interface allows an administrator to fetch logs. This is useful for debugging.

The software is evolving. Originally our members did not have a WordPress account. This presents some difficulty for the moment where we have a hybrid of members, some of whom also have WordPress accounts and some who do not. Eventually we will be forcing everyone to create an account if they wish to continue participating.

I welcome others to use the software. I would appreciate feedback. Developers can participate at GitHub once the software is published there. Eventually it is planned to offer the plugin through WordPress when it meets their standards. You should feel free to branch it, but it would be better for the users if we could avoid conflicts between different branches.

Working on this project has also been an opportunity to learn. Some of the older parts of the software could use some revision, but I don't think there are any major flaws. At this point, I consider the software to be stable. Nothing is commited until it has been tested. Occassionally I have found bugs that were manually patched on our site to hold things over until the next commit.

## Installation
See INSTALL.md.

## Version numbering
Different versioning strategies were originally used in the Alpha stage, but I wanted to base the version on the commit date in some way. On the other hand, I didn't want to start with version 18.x.x. The original version of this plugin had no menu functions. So I retroactively decided that version 1 marked the point where thes basic  features were complete. And subsequent versions in 2019 were 1.M.D (where M and D represent the commit month and date). Major version 2 is for 2020 and major version 3 is for 2021. I also am limiting myself to one commit per day at most. A more conventional versioning system may be used in the future.

## Updating
Currently, only our "Connect" site is using the software. There is an automatic update mechanism, but if there are any failures they are corrected manually. The site is "mounted" on my development system and a git "pull" is issued. (This behaves similarly to FTP but allows the remote file system to be incorporated as part of the local file system.)

## Shortcode types
Several shortcodes are used to provide generated content. Actually there is only a single shortcode with a "type" parameter - e.g. [bookclub type="previous"]. Other parameters could have been used but at the moment, the additional parameters are provided by the URL. As an example, the shortcode type="book" additionally needs an identifier for the book in the database. This comes from a parameter in the URL (?bid=1).

* **book** *(**bid** - book identifier)* Display information about the specified book.
* **forthcoming** *(**gid** - optional group identifier)* Display a list of books that will be read in the future.
* **main** The main page listing the next book each of the different groups will read in the future.
* **news** A sort of micro-blog with news about changes on the book club site. Mostly this has been about changes to the software.
* **previous** *(**y** - optional year, **gid** - optional group identifier)* An overview showing the covers of books that have been previously read by the different groups with links to the book pages.
* **rsvp** *(**eid** - event identifier, **pkey** - member web key *ignored for signed in users*, **status** - optional new status 1=NO, 2=YES, 3=MAYBE)* Upcoming events are shown on the user's profile page with a link to the RSVP page. A link in the invitation EMail sent allows one to set their status.
* **signup** *(**pkey** - member identifier)* Most of our users are migrating from the standalone application. Although it is being phased out, They don't yet have WordPress accounts. This page will allow them to create an account because our WordPress is not configured for open signup.

A WordPress page should be created containing each of these types as part of the configuration process.

## Dashboard menu items
A new menu "**Book Club**" is available when the plugin is activated. Non-administrators will only see the first item (**Profile**). Each page also has a help button. Non-bookclub members will see a button to let them join. WordPress email groups independent of the bookclub may also be shown.

* **Profile** *bc_menu* - Not every WordPress user has a bookclub profile. When there is no profile, this page will let them create one. Once the profile is created, the page shows the settings for that user. If the member is a participant in any upcoming events or if there are "open" events, there will be clickable links to the RSVP page. Envelopes are shown if the forwarder has been activated to allow one to start an email to members of the corresponding group.

The following menu items are only available for the role **Bookclub Admin**. They will not be documented here because they are unimportant for the user and are subject to change. Please see their respective help pages.

* **Book Authors** *bc_authors* - A maintenance page for book authors.
* **Book Covers** *bc_covers* - A maintenance page for book covers.
* **Books** *bc_books* - A maintenance page for books.
* **Places** *bc_places* - A maintenance page for places.
* **Dates** *bc_dates* - A maintenance page for dates when a book will be read.
* **Groups** *bc_groups* - A maintenance page for groups.
* **News** *bc_news* - A maintenance page for new items (micro-blog).
* **Members** *bc_members* - A maintenance page for bookclub members.
* **Events** *bc_events* - A maintenance page for RSVP-able events.
* **EMail** *bc_email* - A maintenance page for creating and sending email.

The following menu item is only available for the role **Administrator**.

* **Settings** *bc_settings* - This is the installation configuration, allowing SMTP email configuration, page configuration, etc.MTP email configuration, page configuration, etc.

## EMail forwarder

This is an optional feature that allows bookclub (or WordPress) users to communicate with each other. A single email account must be dedicated to this but the redistribution is governed by the name part of the "To" field. Sending to "Group 1 &lt;listserver.mysite.org&gt;" will send to everyone in "Group 1" if they have opted in. Sending to "janedoe &lt;listserver.mysite.org&gt;" allows one to send direct messages.

## Testing

A suite of automated tests are being developed using Cypress. This will eventually be published on GitHub.

## Roadmap

There is no timetable, but the following changes are planned:

* Consistent user base (WordPress users only).
* Import book data from some other site.
* Rework the way dates and events are created.
* The plugin should also support movies and maybe other things.
* Android App - iOS if possible.
* Live Webcal link.
* Improve the CSS/JS/PHP.
* Implement an efficient autoloader.

