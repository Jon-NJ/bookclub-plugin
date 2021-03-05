# Requirements #

The software was developed using PHP 7.3 and an up-to-date version of WordPress (5.\*). The WP version may not be critical, but older versions of PHP may not recognize some of the syntax.

There are several library dependencies which can easily be installed using *composer*.

# Install using git #

Those familiar with git know that a project is normally fetched using "`git clone <URL>`" where &lt;URL&gt; typically ends with "`<folder>.git`".

Currently "&lt;folder&gt;" is "`bookclub-plugin`". But it is normally deployed as "`bookclub`" so after cloning, the folder should be renamed. If the code comes as a zip file, it may also need to be renamed. The folder can then be copied/uploaded to the "plugin" WordPress folder on the server. This folder is typically located at "`<wordpress-root>/wp-content/plugins`".

Eventually I hope to make this plugin available directly from WordPress but it is not ready yet.

# Composer #

The project has several PHP package dependencies. The easiest way to install them is to use "composer" ([https://getcomposer.org](https://getcomposer.org)). If you can modify the include_path for your server, you may wish to add a folder specifically for files fetched by composer. Use the file "`composer.json`" (copy it or create a symbolic link to a folder where you can run composer). Run "composer install" and then copy/upload the "`vendor`" folder to a folder in the PHP include path of the server. I don't know of any easier way to do this, but those that use PHP are familiar with this tool.

# Database #

When the plugin is activated, it checks if the tables it needs already exist. Any missing tables will be created. The process works by checking files in the folder "`sql`" that end with ".sql". The base filename, such as members, becomes the suffix of the table name. Our tables will have a "`bc_`" prefix following WordPress prefix (typically "`wp_`"). So the table for members becomes "`wp_bc_members`".

Currently there is no error checking that this process is successful. There is also no removal of the tables when the plugin is unregistered.

# Updating #

A JSON file (updates.json) contains update steps. Normally an updated file (part of a new release) will trigger the update. However it would also be good to check the release notes (CHANGELOG.txt) in case some extra steps are necessary.

# Customizing #

Some pages link to other pages. For instance, when looking at the previous books, one may click on the book cover to jump to a description of that book. Each of these pages is a shortcode so it is expected that pages containing the expected shortcodes have been added to the site.

Several pages should be created, one each for the following short codes:

* `[bookclub type='book']` - a page showing information about a book.
* `[bookclub type='forthcoming']` - a page showing scheduled in the future.
* `[bookclub type='previous'`] - a page showing books scheduled in the past.
* `[bookclub type='rsvp']` - a page that allows members to RSVP.
* `[bookclub type='signup']` - a page that allows members to create a WP account.

After these pages are created, go to "Book Club" | "Settings" in the dashboard. Select the third tab (Pages). Use the drop-down selections to match the page to the shortcode. The software tries to match the contents of the page against the list above and suggest which page to select.

An email account can also be configured on the first tab (EMail) and an email forwarder can be configured on the middle tab (Forwarding). Click the "Help" button at the top of the page for more information.

Hopefully it is possible to configure everything using the Settings page. Fortunately a lot of the presentation and email is rendered using twig templates. See [https://twig.symfony.com](https://twig.symfony.com). The files can be found in the `email` and `twig` subfolders. The help files are in markdown and can be found in the `help` subfolder. More information may be found in developer documentation. Of course, care should be taken when updating because normally these may be overwritten.

# Licensing #

I never expect to make any money from this, it has been a labor of love. I wanted to make the code available with as few restrictions as possible. An acknowledgement is appreciated, but not mandatory. My work experience included application development. Web development was only a hobby. There is always a lot to learn. There are definitely parts that can be improved, but I think some of it is quite good and I want to make it available to other people. I hope you enjoy using the plugin.

Happy Reading. -- Jon
