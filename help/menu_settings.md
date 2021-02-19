# Plugin Settings

### Disclaimer
The software is still in development. Please report any problems to your [site administrator](mailto:{{ support }}) ({{ support }}).

## Tab selection
This page has multiple parts with a tab selector for each sub-configuration. Clicking on the tab displays the configuration for that section. The **Save** button always applies to the complete configuration and is not specific to the current tab.

- **EMail** - Configure the normal email account for invitations and regular email. The logger email and global defines are also configured on this page.
- **Forwarding** - Configure the listserve email forwarder if used.
- **Pages** - Configure the pages containing shortcodes for URL generation.

Changes are not saved automatically. When finished, click **Save**. The **Help** button shows this information.

### EMail configuration
This section configures sending normal and error EMail. Clicking the **Send** button will cause a test email to be sent to your email address.

- **Defines** - General macro definitions. The names shown below should be defined because they are used in some of the templates. But additional definitions may be used.
  - **forwarder** - The email address used for forwarding (only necessary if that feature is activated).
  - **sender** - The email address used for sending invitations, also used in the iCal attachment.
  - **signature** - A signature line shown in emails and a few other places.
  - **support** - The email address to display for anyone who needs assistance. It is also included on the help pages.
  - **who** - The name for the sender, also used in the iCal attachment.

- **Backend** - The type of the mailer (*mail*, *smtp* or *sendmail*). See the <a href='https://pear.php.net/manual/en/package.mail.mail.factory.php' target='_blank'>Pear documentation</a>.

- **Password** - The password to use (if necessary) for the email account. This is stored in the separate password definitions with the name *email_password*.

- **Parameters** - These parameters are used for connecting to the backend mail program. The fields required are dependent on the backend and there may be additional optional fields. Macros may be used.
  - if the name contains '.', it becomes a sub-field of the 'socket_options' value. (e.g. *ssl.verify_peer=false*).
  - A parameter named **password** has special handling. There are separate defines - **email_password** and **forward_password**.

- **Headers** - A list of headers used when sending the email. Headers *From*, *To*, *Subject*, *Content-Type* and *MIME-Version* should normally be included but additional headers may be specified. Macros may be used.
  - If the name is preceded with '@', the header will only be included if the list flag is set (for use with email lists handled by the forwarder).
  - A list of pre-defined macros is given further down.

- **EMail sleep** - Whenever multiple emails are sent, it could be desired to wait before sending the next one. The value is in microseconds. If blank, there is no wait.

- **Error sender** - The sending email address for error messages. There is no authentication so a real account and password are not needed. But the hostname should be correct. This feature may not work on some systems.

- **Error recipient** - The receiving email address for the system administrator who will receive emails when the plugin encounters an error (such as an SQL problem). If left blank, no email is sent.

### Defined macros for headers:
It is necessary to include some dynamic information in the header. This is done by defining various macros. To use it, the macro is an *exact* match of one of the following strings enclosed within **\{\{double braces\}\}**. Additional macros may be added in the **Defines** section.

- **content-type** - One of *text/plain*, *multipart/mixed; boundary=...*, *multipart/alternative; boundary=...* or *multipart/mixed; boundary=...* depending on the type of email being sent.
- **email** - The email address of the recipient.
- **first** - The recipient's first name.
- **last** - The recipient's last name.
- **name** - The full name of the recipient.
- **target** - On forwarder emails, the name part of the recipient or the group tag.
- **utf8from** - On forwarder emails, the name part of the "From" field with UTF8 encoding.
- **utf8name** - The full name of the recipient adjusted for the "To" header with UTF8 encoding.
- **utf8sender** - On forwarder list emails, the name part of the sender with UTF8 encoding.

### Forwarding configuration
This section configures sending normal and error EMail. Clicking the **Send** button will cause a test email to be sent to your email address using the forwarder credentials.

- **Forward IMAP string** - A special string constructed for opening an IMAP resource. Typically the value is something like this "*{imap.myhost.org:993/imap/ssl}INBOX*". See the PHP documentation [imap_open]([https://www.php.net/manual/en/function.imap-open.php). If this field is blank, the feature is not active.

- **Forward IMAP account** - The email address used for the forwarder. Often the value should be "*\{\{forwarder\}\}*".

- **Password** - *Same as above, but configuration for the forwarder*. This is stored in the separate password definitions with the name *forward_password*.

- **Backend** - *Same as above, but configuration for the forwarder*.

- **Parameters** - *Same as above, but configuration for the forwarder*.

- **Headers** - *Same as above, but configuration for the forwarder*.

### Page configuration
These settings help to configure part of the URL used for links on the web page. As an example, the page listing **forthcoming books** shows books with links to another web page - the **books** page. In order to generate these links, the plugin needs to know which page the shortcode *[bookclub type="book"]* is used on. This is configured here.

Each configuration has a drop down list on the left that lists all pages. Selecting one of the pages will fill in the value on the right. When the drop down list is generated, the content of the pages is inspected to try to match the expected shortcode. If the shortcode is recognized, the text **(matches)** will be appended to the page name if the page is currently selected. If the page is not selected it will instead show the text **(suggested)**. These two texts come from the same condition and they only differ because in one case the page has already been selected so it does not need to be suggested.

One other note, depending on the WordPress configuration, it is possible that instead of the slug, the web page is referred to by the page identifier (e.g. "?p=3"). There are other possible configurations and the software does not recognize them all.

- **Book page** - Page showing a single book. *[bookclub type="book"]*
- **Forthcoming page** - Page listing future books. *[bookclub type="forthcoming"]*
- **Previous page** - Page showing previously read books. *[bookclub type="previous"]*
- **RSVP page** - Page allowing someone to RSVP without logging in. *[bookclub type="rsvp"]*
- **Signup page** - Page allowing someone to signup for a WordPress account. *[bookclub type="signup"]*
