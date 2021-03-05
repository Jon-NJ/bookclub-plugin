# Developer documentation for the Book Club plugin #

The document is not yet as detailed as I would hope, but provides gives a staring point.

## Overview ##
This plugin mainly handles two similar types of content. 

* **ShortCodes** are used for rendering shortcode content and usually represent a single page.
* **MenuItems** are used for rendering single menu items.

Each of these may specify their own CSS, JS and typically other items such as help files. When the plugin loads, it creates a global **Manager** object. PHP files in the menuitems, routes and shortcodes folders are all included using a "`*.php`" wildcard. As each menu or page is included, a single instance object is created at the bottom of the class. The instance object registers itself with the manager.

A third type of page is used for "**Paths**" and there is also a **REST** handler:

* ICalDownload - download an ical file for a given user and event.
* FetchLogs - a REST based interface to fetch log information.
* ForwardEMails - a CRON target for the email forwarder.

Every file in the target folders is automatically included. This may be somewhat inefficient because all classes are always loaded. Originally there were not that many classes. A better solution using some form of an autoloader may be implemented in the future, but this solution does have a certain elegance and makes it trivial to add new pages.

When content needs to be rendered, the manager will invoke the "render" method of the given page or menu. Rendering normally involves (1) preparing the content in "JSON" form (a PHP array) and (2) rendering it using a "`twig`" template. Generally, there is one twig template per page. Twig templates are also widely used for other things (email and help).

Of course most pages also need to handle client side actions, POST, AJAX or additional GETs. All necessary actions are handled within the class for that page or menu.

Each page is initialized using a key and dictionary array specifying information about the page. This is documented in more detail [further down](#page-construct-data)).

When a page needs to fetch or change values in the database, it uses a table object. This may be for a single table or a set of joined tables. The table objects all inherit from DatabaseTable methods to insert, update, delete, select, etc. But it also serves as a minimalist query builder. This will also be documented in more detail [further down](#database-handling).

Each page may have a help file.  This is also implemented with "`twig`" using the Markdown extension.

EMails are also composed using "`twig`" which is even capable of handling Mime types and generating an `iCal` file.

## Folder structure ##
Filenames that are completely in upper case are non-code such as README, LICENSE, etc. Mixed case filenames are for PHP classes of the same name. All PHP code uses a namespace "`bookclub`" to avoid conflicts with other plugins.

- **css/** - Cascading Style Sheets - there is no single css file for the project. This may change in the future if the css becomes more unified. Each menu item or shortcode page specifies it's own css.
  - menu_&lt;slug&gt;.css - Style sheet for the given menu.
  - page_&lt;type&gt;.css - Style sheet for the given page.
- **database/** - PHP Classes for accessing the database.
  - DatabaseIterator.php - A general iterator for looping through result sets.
  - DatabaseTable.php - The base class for pure and joined tables. It also provides several query builder functions.
  - Join&lt;tablenames&gt;.php - Classes used for joined tables.
  - Table&lt;tablename&gt;.php - Classes used for pure tables.
- **email/** - Twig templates for generating emails.
  - email_\*.twig - Various template files for generating email.
- **framework/** - Classes and libraries that provide the skeleton of the plugin.
  - EMail.php - A class used for sending emails.
  - Manager.php - A class for handling rendering of pages and menus as well as registering/unregistering and other WordPress functions.
  - MenuItem.php - A class derived from Page for handling a single menu item.
  - Page.php - A base class for handling shortcode types, menu items and Paths.
  - Path.php - A class derived from Page for handling a URL hook.
  - REST.php - An abstract class for registering the handler for a REST item.
  - ShortCode.php - A class derived from Page for handling a single shortcode.
  - database.php - Responsible for including all database classes. Also includes general purpose global database functions.
  - layouter.php - Responsible for rendering data using templates.
  - library.php - Responsible for including all other pages. Also includes general purpose global functions.
  - logging.php - Responsible for initializing and configuring logging.
  - pages.php - Responsible for including menu and page classes and triggering the menu construction.
- **help/** - Help files in markdown.
  - menu_&lt;slug&gt;.md - Help for the given menu item.
  - page_&lt;slug&gt;.md - Help for the given (shortcode) page.
- **images/** - Images used as icons.
  - \*.png - Icon files.
  - \*.svg - Scalable images.
- **js/** - Javascript files - there is no single js file for the project. Each menu or page can specify it's own script file.
  - menu_&lt;slug&gt;.js - Javascript for the given menu.
  - page_&lt;type&gt;.js - Javascript for the given page.
- **menuitems/** - PHP Classes for handling a specific menu item.
  - Menu&lt;Name&gt;.php - PHP class for a single menu item.
- **routes/** - PHP classes for handling some non-rendering functions.
- **shortcodes/** - PHP Classes for handling a page of a specific shortcode type.
  - Page&lt;Type&gt;.php - PHP class for a single shortcode type.
- **sql/** - A collection of scripts for creating the necessary tables.
  - &lt;tablename&gt;.php - A single script for creating a table and it's indexes.
- **twig/** - Twig template files used for rendering content other than emails.
  - calender.twig - Template used to render the calendar.
  - email_log.twig - A template used for rendering the email log.
  - markdown_help.twig - A helper twig file used for rendering the help markdown files.
  - menu_&lt;slug&gt;.twig - Template used for rendering a specific menu item.
  - page_&lt;type&gt;.twig - Template used for rendering a specific page.
  - rsvp_who.twig - Template used for rendering RSVP participants for AJAX.
  - select_participants.twig - Template used for rendering participants for AJAX.
  - select_recipients.twig - Template used for rendering recipients for AJAX.
  - upcoming_books.twig - Template used for rendering upcoming books fetched using AJAX.
- bookclub.php - Root plugin file.
- composer.json - Defines the dependencies for the plugin.
- updates.json - Defines steps necessary when updating the plugin to a newer version.
### non-code files ###
- CHANGELOG.txt - Version history.
- DEVELOPER.md - This file.
- INSTALL.md - Installation documentation.
- LICENSE - Software license.
- README.md - General information about the software.

## File inclusion ##
- bookclub.php includes library.php.
- library.php first loads the autoload for composer. It then loads:
  - database.php
  - logging.php
  - layouter.php
  - EMail.php
  - Manager.php
  - Page.php
  - MenuItem.php
  - ShortCode.php
  - REST.php
  - Path.php
  - pages.php
- database.php automatically includes all files in folder database/.
- layouter.php triggers class loading of some Twig classes.
- pages automatically includes all files in folders routes/, shortcodes/ and menuitems/.

## File documentation ##

### Manager.php ###
todo

### Page.php ###
todo

### DatabaseTable.php ###
todo

### css files ###
todo

### database files ###
todo

### help files ###
todo

### javascript files ###
todo

### menuitem files ###
todo

### shortcode files ###
todo

### routes files ###
todo

### sql files ###
todo

### twig files ###
todo

## Page construct data ##
todo

## Database handling ##
todo
