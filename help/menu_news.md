# News maintenance

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
- **Post Date Time** - Every news item has a date and time that uniquely identifies it. For searching, only the date is necessary. For adding, the time will also be included. But normally, this field will be empty when adding and the current date/time is used. (Read only in edit mode.)
- **Maximum Age** - The number of months for the age of the post. *Only visible in **Start**/**Search** mode.*
- **Poster** - The name of the person that posted the news. This should normally be the WordPress login name, but there are no restrictions. When adding, the login of the current user is used if the field is empty.
- **Post** - The text of the post with HTML.

## Search Results
- **When** - Date of the post.
- **Who** - The name of the post creator.
- **Post** - The text of the post.

## Editing
Changes are not saved automatically. When finished, click **Save**.
