# Book maintenance

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
- **Group ID** - Unique identifier for a group to search for. In edit mode it is the identifier of the group being edited and it is read only. The field may be specified when adding, but normally it should be left empty to use the next number. Values under 1000 are reserved for book club groups. There are several types of groups. Each one has a reserved range of 1000.
- **Group types** - There are several toggles to restrict the search. Clicking one will toggle it, only one can be selected so it also clears a previous selection. One type must be selected to activate the **Add** button.
  - **Book club** - Restrict the search or prepare to add a book club group.
  - **Select list** - Restrict the search or prepare to add a selection list (such as for an email list).
  - **WordPress list** - Restrict the search or prepare to add a WordPress email list.
  - **Announcements** - Restrict the search or prepare to add a WordPress announcements list.
- **Tag** - A short description for the given group. This will be shown in drop down boxes and on other pages such as the book, previous and forthcoming pages.
- **Title** - This is a longer description of the given group. It will be shown on the profile and members pages for the group membership check box.
- **URL** - *Edit Book Club group only.* A URL for a WordPress page for information about the given group.

### Edit Template fields - *Book Club groups only*
Defining a template for a group allows an "Event" to be created based on a "Date". Some editing may still be required, but this can reduce the effort needed. In the "Dates" page, a "Create Event" button will be displayed.
- ** Max ** - If not empty, it is the default maximum number of participants.
- ** Start Time ** - If not empty, it is the default hh:mm(:ss) start time.
- ** End Time ** - If not empty, it is the default hh:mm(:ss) end time.
- ** Event ID ** - If not empty, it is a Twig template for generating an event ID.
- ** Include ** - **None** or a group to automatically include for an event.
- ** What ** - If not empty, it is a Twig template for generating the event summary.
- ** Description ** - If not empty, it is a Twig template for generating the event description.

## Twig templates
For a detailed description, consult the [Twig](https://twig.symfony.com/) documentation. This feature may be somewhat complicated so you may wish to involve the site administration if you want to configure a template.
### Defined variables:
- **date** - The date of the meeting, it can be formatted in various ways.
- **group.**
  - **id** - The group identifier.
  - **tag** - The short group description.
  - **description** - The long group description.
- **book.**
  - **title** - The title of the book.
  - **author** - The author of the book.
- **place.**
  - **id** - The identifier of the place.
  - **name** - The short name of the place.
  - **address** - The street address of the place.
  - **directions** - Multi-line directions.
- **name** - A substitute for the **\{\{name\}\}** macro in the future description.
- **first** - A substitute for the **\{\{first\}\}** macro in the future description.
- **last** - A substitute for the **\{\{last\}\}** macro in the future description.

## Edit Selection, WordPress or Announcement List
When editing an non-bookclub group, additional buttons and fields are shown to control who is in the list. Candidates for the list inclusion vary depending on what kind of group it is. Originally all candidates are not included so they are in the box on the left. If candidates are selected in this list and the **>>** button is clicked, they will be moved to the selected list on the right. If selected members in the box on the right are selected and the **<<** button is clicked, they will be moved back to the candidate list on the left.

Bookclub members that are shown in italic and grayed out are inactive.

### Selection Buttons - *Selection list only*
- **Left** - Select members on the left side that are not yet members of the list based on the selections.
- **Groups** - When the drop down selection **All Groups** is shown, it means there is no constraint or exclusion based on any group. Selecting a group from the drop down menu will only include members in that group. If the **Exclude**  button is selected, members from the selected group will be excluded instead.
- **Exclude** - Toggles the selection of a group to exclude instead of include. The button is disabled when **All Groups** is selected.
- **Active** - Throggle active users, ignored when not selected, only active when selected or only inactive when crossed out.
- **Right** - Select members on the right side that are members of the list based on the selections.

**Throggling**. The **Active** button is a "throggle" (three state toggle). Click once on **Active** and then click **Left**. The **Active** button is positively active and only members are active will be in the result list. Click again on **Active** and again on **Left**. The **Active** button has a slash through it and it is negatively active. All members **not** active are selected. Click one more time on **Active** and it returns to the neutral state.

Any time there are changes to the group member list, they will not take effect until **Save** is clicked.

## Search Results
- **ID** - The unique identifier for the group.
- **Type** - The group type. Meeting dates are restricted to using the Book club type. Users can toggle their book club group membership on their profile page. But EMail lists can only be configured by administrators.
- **Tag** - A short description for the given group.
- **Description** - A longer description for the given group that will be shown on the profile and members pages with the check box selection.

## Editing
Changes are not saved automatically. When finished, click **Save**.
