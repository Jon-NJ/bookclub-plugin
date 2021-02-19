# Book Club Profile

### Disclaimer
The software is still in development. Please report any problems to your [site administrator](mailto:{{ support }}) ({{ support }}).

## Profile Settings - Changes are saved automatically.

**Help** - Show this help screen.

**Send** - A test email will be sent to you. Normally it should arrive within a few minutes. If you get no email, there may be some problem. Check your spam folder. You could also change to another email account. If you can't solve the problem yourself, get in touch with a site administrator. Please do not abuse this feature, but you are welcome to use it to diagnose problems on your own.

**Name and email** - This is your WordPress name and email address. The values can **only** be edited in the WordPress profile.

**Your Web key** - This is a unique identifier assigned to you that allows you to RSVP without needing to log in.

**Reset key** - The web key is a bit like a password. If you think your key has become public you can create a new one after confirming the change. Emails you have already received that allow you to RSVP will no longer work because they are based on this key. But you can always RSVP from your profile here.

**Settings** - Configuration flags.
- **Format** - *Text Only* or *HTML*. Modern email programs are capable of showing HTML so this is the preferred option. A text version is also included with HTML email.
- **Attach iCalendar** - When invitations are received, an iCal file will normally be attached. This facilitates adding the event to your electronic calendar if the email client is capable. The attachment can be ignored or you can turn off this feature if it causes problems.
- **No EMails** - *A confusing negative,* setting to Yes means that you will **not** be sent any email by the bookclub software. *(Although it is always possible for an administrator to personally send you an email.)* You can change this yourself, but the flag might be set by an administrator if your emails are bouncing. We need to do this to avoid being put on a spam list.
- **Active** - Your bookclub status, if not active you normally will not be invited to events. In the future we may also use this flag as one of multiple criteria to remove old accounts.

**Privacy** - Privacy configuration.
- **EMail Address** - Private/Public. If your email address is public, other book club members may see it on an RSVP.
- **Receive EMail from others** - No/Yes - An email forwarder (list server) is available. This allows your bookclub group selection to also serve as a list server for email conversations. A more detailed explanation is given at the bottom of this help. This is an opt-in feature, but everyone is encouraged to select it.

**Book Club groups** - This is a list of bookclub groups. When the check mark is set, it indicates that you would like to be invited to future meetings of the given group. But it is also not a guarantee that you will be invited. Some meetings may be private, restricted to a core group.
{% if forwarder %}
Clicking on an envelope should start an email for distribution to members of the group who have opted-in.
{% endif %}

**WordPress groups** - There may be additional email listserver groups you can subscribe to. More information can be found at the bottom.
{% if forwarder %}
Clicking on an envelope should start an email for distribution to members of the group.
{% endif %}

**My future meetings** - These are meetings for which you have been invited. You can click on the event to set your RSVP response.

**Open meetings** - These are future meetings for which you have not been invited. It may be for a group you haven't signed up to or a group with restricted participation. But you are welcome to invite yourself. Clicking on the link will automatically invite you and open up the RSVP form. But if the meeting is already completely booked you may find yourself on the waiting list.

**Upcoming books** - These are books for the groups you have joined. If you aren't yet in any group, all the future books are shown.

**GDPR compliance** - To make it possible for you to remove any data we have about you, you can delete your account by clicking on the **Remove all data** button and confirm. The action is not reversible.

## EMail forwarder
{% if forwarder %}
The forwarder is <span style='color:green'>available</span> at the email address [{{ forwarder }}](mailto:{{ forwarder }}).
{% else %}
The forwarder is <span style='color:red'>not available</span> at the moment.
{% endif %}

In computer-speak, this is often called a "**LISTSERV**" (list server). Typically when you send email to the list server address, it will be forwarded to everyone in the group. A separate email account would be configured for each list.

This forwarder is slightly different, it is configured with a single email account. In order for it to handle multiple lists and direct messages, the name part of the email address is taken as the list name or it identifies a user in order to send direct messages.

The forwarder also serves as an anonymizer. If you send an email to the forwarder, your name and login ID will be disclosed but your email address will be kept private. The login name is unique, so it works as the best identifier. It is considered semi-private, only available to people with an account.

What this means for our bookclub is that for the group with the tag "Group 1" you would send an email to "Group 1 &lt;{{ forwarder }}&gt;".

It is also possible to send a direct message to a single user (e.g. "janedoe &lt;{{ forwarder }}&gt;"). Instead of the group name, use the login ID or user name. ("Jane Doe" should also work.)

This is an opt-in feature. But it is only useful if people opt-in, so you are encouraged to enable this feature. Message can be text or text/html, but attachments will not be forwarded.

The feature is still experimental. Here are a few things to know before you use the feature:
- You can only send from the email address registered in WordPress. **EMails from other addresses will be ignored.**
- It is a WordPress only feature - members who have not yet created an account cannot participate.
- Only users with the opt-in setting 'Receive EMail from others' of 'Yes'. Can send messages. This is because it must be possible to reply to them with a direct message.
- There are various categories of forwarding behavior. The rules for who can send vary by the category.
  - Direct messages. The receiver must have opted-in, but they do not need to be in any groups.
  - Bookclub groups. The sender can be an administrator or a member of the group. Only users who have opted-in will receive the message.
  - WordPress groups. The sender can be an administrator or a member of the group. The opt-in flag is not considered for receiving.
  - WordPress announcements. The sender can only be an administrator. The opt-in flag is not considered for receiving.
- Direct messages to other users should not be considered completely private. The owner of the forwarding email account can always look at them. But generally this will only be done if there are some problems.
