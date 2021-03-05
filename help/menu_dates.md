 # Date maintenance

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
- **Groups** - By default, all groups are searched. A filter for one of the book club groups can be activated. The **All Groups** selection is not available in edit mode. The drop down selection is used to select the group.
- **Create Event**/**Update Event** - *Edit only, only visible if the group has a template for the event ID.* An event is created if none exists, or the existing event is overwritten. See the Groups help page for template information.
- **Edit *event*** - *Edit only, only visible if the group has a template and the event exists*. Jump to editing the linked event.
- **Date** - The date of the meeting. This can also be selected on the calendar. The calendar synchronizes when there is a valid entry.
- **Maximum Age** - past meetings can be filtered out for the specified number of months. *Not visible in **Edit** mode.*
- **Book** - Partial title of the book. Existing titles should be available as drop down suggestions. For exact matches, the book identifier is shown in the box on the right. The author is also automatically supplied when a book is selected.
- **Author** - Partial name of the author. Existing authors should be available as drop down suggestions. For exact matches, the author identifier is shown in the box on the right.
- **Place** - Partial place name. Existing places should be available as drop down suggestions. For exact matches, the place identifier is shown in the box on the right.
- **Hidden** - Only in edit mode. A check mark toggle to set the hidden flag. Hidden dates will not be shown on the public forthcoming page, but will be shown to those in that group.
- **Private** - Only in edit mode. A check mark toggle to set the private flag. Dates with this flag will show as private on the main, forthcoming and profile pages. The meeting place will not be shown.
- **Priority** - Only in edit mode. Zero or the number of hours prior to an event when RSVP will be open to all. This overrides the private flag.
- **Calendar** - The current month is shown by default. It is possible to navigate forward and backward by one month or one year by clicking the **<**, **>**, **<<** and **>>** buttons. Clicking on the month name reverts to the current month. Clicking on a date will select it.

When adding a new date, a specific group must be selected. The selection "All Groups" causes the operation to be disabled. Additionally, the date must be valid. The author, book and place must be correct. (Place can be empty.) The same is true in edit mode when saving.

## Search Results
- **H=Hidden** - A check mark if the date should be excluded from the forthcoming books list.
- **P=private** - A check mark if the meeting on that date is private.
- **P=priority** - Blank or the number of hours prior to a meeting when signup is open to all.
- **Date** - The date of the meeting
- **Group** - One of the book club groups.
- **Where** - The place name or empty if none selected yet.
- **Book** - The book title.
- **Author** - The book author.

Meetings in the past are shown in gray.

## Editing
It is not possible to save if any of the values are invalid. It is possible to change the group and date values.

Changes are not saved automatically. When finished, click **Save**.
