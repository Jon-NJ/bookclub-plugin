# Member maintenance

### Disclaimer
The software is still in development. Please report any problems to your [site administrator](mailto:{{ support }}) ({{ support }}).

## State Model
The page uses three states - **Start**, **Search** and **Edit**. When one first navigates to the page, it will be in **Start** state. Various search fields are presented. Clicking the **Search** button performs a search with possible results listed below. Clicking any of the result lines will select that item and switch to **Edit** mode. But it is also possible to perform a new search. Clicking **Add** will create a new item and then transition to **Edit** mode. From this mode, the **Search** button is replaced with a **Restart** button. Clicking this button will discard any changes and revert to the **Start** mode. **Delete** is also only available in **Edit** mode. If the delete is confirmed, the item is deleted and it goes back to the **Start** state.

## Tool Buttons
- **Help** - Shows this page. Please be sure to click the close button in the top right when finished.
- **Add** - Add a new item. This button is disabled during **Edit** and may also be disabled if some of the fields are invalid.
- **Search** - Start a search based on the given parameters. Empty fields will be ignored for the search, in other words, all items will be matched.
- **Restart** - *Only visible in **Edit** mode.* Changes are discarded, return to **Start** mode.
- **Save** - *Only enabled in **Edit** mode.* Changes will be saved. If the button is disabled in edit mode, it indicates that some field is invalid or that saving is not necessary (e.g. nothing has changed).
- **Delete** - *Only enabled in **Edit** mode.* The item will be removed from the database.

## Search/Edit fields
- **Groups**:
 - **Start**/**Search** mode - Members can be searched by membership in book club groups and selection lists. Both types are available in the drop down menu. Selecting one of these will constrain the search results to only members included in the given group. The selection **All Groups** removes any constraint.
 - **Exclude** - Reverse the sense of the group selection, exclude instead of include members of the selected group. This is a toggle. It is not used in **Edit** mode. The button is disabled when **All Groups** is selected.
 - **Active** - In **Start**/**Search** mode this is a throggle (see below) that allows a positive or negative filter for **Active** status. In **Edit**, it is a toggle to set the value for the member being edited.
 - **Edit** mode - All groups (book club and email) are shown with a check box to indicate whether the member is in that group. These can be toggled to change the group membership.
- **Member ID** - The unique member identifier. (Read only in edit mode. Also in edit mode, the web key is shown on the same line.)
- **Reset Key** - *Edit mode only.* Button to generate a new web key for selected user. Unlike in the same button in profile, the value is not automatically saved. Changing this value invalidates the old key used for signup or RSVPs.
- **Web Key** - A unique universal identifier that may be used for RSVP without having to log in.
- **WP ID** - The unique WordPress identifier, "\*" to match all/only WordPress users. (Read only in edit mode, only users with a WP account.)
- **WP login** - Partial WordPress login. (Read only in edit mode, only users with a WP account.)
- **WP role** - *Only visible in **Edit** mode* and only users with a WP account - usually subscriber or administrator. Clicking the button to the right opens up the WordPress profile page for that user.
- **Signup URL** - *Only visible in **Edit** mode* and only users without a WordPress account. The signup link is shown in (readonly).
  - There is a copy icon. Clicking this copies the link to the clipboard.
  - There is an email icon. Clicking this button sends an email to the member with information about signing up. The results of the last send are shown to the right of the button.
- **Name** - Partial member name if searching, otherwise the complete name.
- **E-Mail** - Partial email address if searching, otherwise the complete email address.

  In **Edit** mode if the user has a WordPress account, the name and email fields are combined and become read only.

- **Settings** - *Only visible in **Edit** mode.*
  - **Format** - A user can configure that email is sent in text mode only or combined text and HTML.
  - **Attach Calendar** - For invitations, normally an iCal file can be attached or not.
  - **No EMails** - When set to Yes, no email will be sent but the user remains active.
  - **Active** - When not active, the member will not be included in upcoming events. Normally they will not receive email although it is still possible depending on what selections are made.

- **Privacy** - *Only visible in **Edit** mode.* Privacy configuration.
  - **EMail Address** - Private/Public. If your email address is public, other book club members may see it on an RSVP.
  - **Receive EMail from others** - No/Yes - If activated other users may be able to send you emails using a web interface, or perhaps this will be used for inclusion in a list-server. *(Not used yet.)*

- **Last Visit** - (Not available in edit mode.) Filter search results for members that have not been on the web page for the given number of months. The search can be "more than" or "less than".

When adding, the member identifier and web key are generated automatically and should be left empty.

**Throggling**. In search mode, the **Active** button is a "throggle" (three state toggle). Click once on **Active** and then click **Search**. The **Active** button is positively active and only members are active will be in the result list. Click again on **Active** and again on **Search**. The **Active** button has a slash through it and it is negatively active. All members **not** active are selected. Click one more time on **Active** and it returns to the neutral state.

## Search Results
- **ID** - Unique member identifier.
- **Key** - Unique key for web interface (may be used for RSVP).
- **WP ID** - WordPress identifier.
- **WP login** - WordPress login name.
- **Name** - The name of the member.
- **E-Mail** - The email of the member. For WordPress members, this is the WordPress email instead of the book club email.
- **Last visit** - The last time the member was on the web page (or &lt;never&gt;>).

Inactive users are shown gray in italics.

## Editing
Changes are not saved automatically. When finished, click **Save**.
