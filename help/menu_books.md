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
- **Book ID** - Unique identifier for this book (read only in edit mode, ignored when adding).
- **Title** - Title of the book. Existing titles should be available as drop down suggestions.
- **Cover** - Filename of the cover art or blank if not uploaded.
- **Author** - Author name. An additional (read only) box is shown which shows the unique identifier of the author. If this field is blank, the author has not been found and it is not possible to add or save. The names of authors in the database should be available as drop down suggestions.
- **Description** - Short HTML formatted book description.

## Search Results
- **ID** - The unique identifier for the book.
- **Title** - The title of the book.
- **Author** - The name of the author.

## Editing
It is not possible to save if an invalid author is entered. (See the note above under Search/Edit fields.)

Changes are not saved automatically. When finished, click **Save**.
