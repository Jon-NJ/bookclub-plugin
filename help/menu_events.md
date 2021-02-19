# Creating Events and sending invitations

### Disclaimer
The software is still in development. Please report any problems to your [site administrator](mailto:{{ support }}) ({{ support }}).

## Special Note
This feature is one of the more complicated ones available. It seems worthwhile to give a short overview. If you have not already used other features, please acquaint yourself with the **State Model**. In short, first search for an existing event and click on it to start editing it, or create a new event by entering an event id, start date/time, the "what" subject and the "description" body and then clicking **Add**.

The event description is created in **Raw** HTML. This is also the initial "view". It is possible to click **HTML** or **Text** to see how the event will be rendered in these formats. These are read-only views.

The **Participants** view shows members (potential participants) on the left and selected participants on the right. Only those on the right will be invited. Add participants one-by-one manually by clicking on them and then on the **>>** button, or use the group filter, click **Left** to select from those listed in the left column and once again on the **>>** button.

Be sure to click **Save** whenever there is any change, including changing who the participants are. It is not possible to click **Send** if there are unsaved changes. Participants that have not yet received the invitation show an envelope with a red slash. Those who have received the invitation show a normal envelope. The **Clear** button will reset everyone to unsent.

Please make sure everything is correct before you click **Send**. Once the job starts, it is no longer possible to cancel it. The job runs at the server and should only quit once all emails are sent. The display in your browser should keep refreshing until the job is completed.

When the job starts, it sets a "running" flag so that two identical jobs won't be running at the same time. There is the possibility that the job fails to complete before it can clear the "running" flag. If this happens, there is also a time-out (currently five minutes) to allow the job to be restarted.

The **RSVP** view allows the status of invited participants to be changed. Click on a participant and then **Yes** / **No** / **Maybe** to change the participant's status. It is also possible to add or remove them from the waiting list using the **Wait** / **Unwait** buttons. **These two buttons should be avoided because they can disturb the ordering of the waiting list.** The **Next** button will "unwait" the next person on the waiting list. More information about this view can be found later in this document.

If you still have any confusion, read the rest of this help or contact the site administrator.

## State Model
The page uses three states - **Start**, **Search** and **Edit**. When one first navigates to the page, it will be in **Start** state. Various search fields are presented. Clicking the **Search** button performs a search with possible results listed below. Clicking any of the result lines will select that item and switch to **Edit** mode. But it is also possible to perform a new search. Clicking **Add** will create a new item and then transition to **Edit** mode. From this mode, the **Search** button is replaced with a **Restart** button. Clicking this button will discard any changes and revert to the **Start** mode. **Delete** is also only available in **Edit** mode. If the delete is confirmed, the item is deleted and it goes back to the **Start** state.

## Tool Buttons
- **Help** - Shows this page. Please be sure to click the close button in the top right when finished.
- **Add** - Add a new item. This button is disabled during **Edit** and may also be disabled if some of the fields are invalid.
- **Search** - Start a search based on the given parameters. Empty fields will be ignored for the search, in other words, all items will be matched.
- **Restart** - *Only visible in **Edit** mode.* Changes are discarded, return to **Start** mode.
- **Save** - *Only enabled in **Edit** mode.* Changes will be saved. If the button is disabled in edit mode, it indicates that some field is invalid or that saving is not necessary (e.g. nothing has changed).
- **Delete** - *Only enabled in **Edit** mode.* The item will be removed from the database.
- **Send** - *Only visible in **Edit** mode.* *Only active when changes are saved, the send job is not already running and there are unsent invitation emails.* Send unsent invitations.
- **Clear** - *Only visible in **Edit** mode.* *Only active when changes are saved and some invitations have already been sent.* The "sent" flag is cleared from all users so invitations can be sent again.

## Search/Edit fields
- **Event ID** - An event needs a unique identifier. By convention, this identifier is based on the date and group ID - YYYYMMDD_bc_x where x is the group id. This value can be changed, but a warning is presented if there have already been any emails sent out because they will contain an invalid RSVP link.
- **Max** - The maximum number of participants or zero if unlimited. *Only visible in **Edit** mode.*
- **Start DateTime** - The date and start time of the event. The format is YYYY-MM-DD with an optional start time HH:MM (24 hour clock). If the time is not specified, a default value is used.
- **End** - The end time of the event HH:MM (24 hour clock). *Only visible in **Edit** mode.*
- **Private** - This flag hides the event on the profile page so users cannot add themselves. It is only available in **Edit** mode and it is not visible in all views.
- **Priority** - Zero or the number of hours prior to an event when RSVP will be open to all. This overrides the private flag. It is only available in **Edit** mode and it is not visible in all views.
- **Maximum age** - This allows events older than the given number of months to be filtered out from the search results. *Not visible in **Edit** mode.*
- **What** - This is the subject of the event - a short description.
- **Where** - Usually the street address of the event such as "Prenzlauer Alle 208, 10405 Berlin".
- **Map** - An optional URL for the event location. Typically this is a google maps search link such as:  
  http://maps.google.de/maps?q=Prenzlauer+Alle+208+10405+Berlin+Germany   
 Next to the entry field there is a link-out icon that should open the map link in a new tab window assuming it starts with "http://" or "https://".
- **Description** - A detailed description of the event with HTML markup and macros.

## Search Results
- **ID** - The event identifier.
- **P=private** - The private flag indicates that only administrators can add people to an event.
- **P=priority** - Zero or the number of hours prior to a meeting when signup is open to all.
- **Start Time** - The start date and time of the event.
- **Subject** - The "what" short description of the event.

## Editing
There are six views. Click on the button to switch views.

- **Raw** - This shows the raw HTML and macros. This field can be edited.
- **HTML** - This shows a "rendered" HTML with macros filled in. It is not possible to edit in this view.
- **Text** - This shows a "rendered" text version of the event. Macros are filled in and the HTML has been translated as much as possible to text. For recipients who only receive email in text form, this is approximately what they will see.
- **Participants** - The left selection list shows users who are **not** included and the right selection list shows users who **are** (invited) participants. Book club members can be selected and then moved in either direction. This view is described in more detail below.
- **RSVP** - This shows the RSVP status of the included participants. It is also possible to make changes. This view is described in more detail below.
- **Log** - This has two sub-views - **EMail** and **RSVP**. The EMail log shows when the invitation was sent to each person included in the event. The RSVP log shows when there were any RSVP status changes but it does not show changes in waiting list status.

Changes are not saved automatically. When finished, click **Save** and then **Send** if necessary.

## Macros
It is possible to include some dynamic information in the body of the email by including certain strings in the raw body. A macro is an *exact* match of one of the following strings enclosed within **\{\{double braces\}\}**.

- **name** - user full name (first last).
- **first** - user first name.
- **last** - user last name.
- **email** - the recipient's email.
- **profile** - a URL link to the bookclub profile page for members with a WordPress account.
- **signup** - a URL specific to the member for creating a WordPress account (non-WordPress users only).

## Participants View
This view controls the list of participants for the event. Originally all bookclub members are not participants so they are in the box on the left. If members are selected in this list and the **>>** button is clicked, these members will be moved to the participant list on the right. If participants in the box on the right are selected and the **<<** button is clicked, they will be moved back to the member list on the left.

Members that are shown in italic and grayed out are inactive. They normally should not receive any emails.

Underneath the **>>** buttons there are **Send** and **Clear** buttons. These apply to the participants selected on the right. These buttons make it possible to clear the send flag or send invitations only to the selected participants. Some care should be taken here that everything has been saved because the buttons are not disabled when there is unsaved information. Even if a participant has already been emailed, it is possible to do it again.

### Participant Buttons
- **Left** - Select members on the left side that are not yet participants based on the selections.
- **Groups** - When the drop down selection **All Groups** is shown, it means there is no constraint or exclusion based on any group. Selecting a group from the drop down menu will only include members in that group. If the **Exclude** button is selected, members from the selected group will be excluded instead.
- **Exclude** - Toggles the selection of a group to exclude instead of include. The button is disabled when **All Groups** is selected.
- **Active** - Throggle active users, ignored when not selected, only active when selected or only inactive when crossed out.
- **Right** - Select participants on the right side based on the selections.

**Throggling**. The **Active** button is a "throggle" (three state toggle). Click once on **Active** and then click **Left**. The **Active** button is positively active and only members are active will be in the result list. Click again on **Active** and again on **Left**. The **Active** button has a slash through it and it is negatively active. All members **not** active are selected. Click one more time on **Active** and it returns to the neutral state.

Any time there are changes to the participant list, they will not take effect until **Save** is clicked.

## RSVP View
The selection box on the left shows the members included in the event grouped by their RSVP status - Yes/Maybe/Yes Waiting/Maybe Waiting/No/No response. Clicking on a participant will activate buttons depending on the current status to allow the status to be changed.

- **Yes** - Change RSVP status - the member is planning to come.
- **No** - Change RSVP status - the member is not planning to come.
- **Maybe** - Change RSVP status - the member might come, reserve a seat.
- **Wait** - Move to waiting list.
- **Unwait** - Remove from waiting list (no notification sent).
- **Next** - Remove the top person from the waiting list and send a notification email so the user knows that they are no longer waiting.

Underneath these buttons, the waiting list is shown. The top user is the next one who will be unwaited.

**Please note:**   
It is important to be aware that any changes in this view take effect immediately. It is assumed that changes have been saved.
   
**Additionally**, when someone is moved off the waiting list because someone who wasn't waiting was changed to **No** or from clicking **Next**, an email will be sent to notify the member that they are no longer waiting. It will not be sent if **Wait** or **Unwait** are clicked. Using **Wait** and **Unwait** only changes the status, but the waiting list order may be disturbed. The maximum participants will be ignored for any of these three buttons.

Another point to be aware of, there is an internal counter for how many people have **Yes**/**Maybe** status but are not on the waiting list. Previously this value was shown but it was always possible that it got out of sync. This value is important in determining whether someone goes on the waiting list. While it is still possible for the value to become incorrect, it will be corrected any time a user status is changed in this view. So in normal circumstances the value should be accurate.