# Cover maintenance

### Disclaimer
The software is still in development. Please report any problems to your [site administrator](mailto:{{ support }}) ({{ support }}).

## State Model
The page uses three states - **Start**, **Search** and **Edit**. When one first navigates to the page, it will be in **Start** state. Various search fields are presented. Clicking the **Search** button performs a search with possible results listed below. Clicking any of the result lines will select that item and switch to **Edit** mode. But it is also possible to perform a new search. Clicking **Add** will create a new item and then transition to **Edit** mode. From this mode, the **Search** button is replaced with a **Restart** button. Clicking this button will discard any changes and revert to the **Start** mode. **Delete** is also only available in **Edit** mode. If the delete is confirmed, the item is deleted and it goes back to the **Start** state.

## Tool Buttons
- **Help** - Shows this page. Please be sure to click the close button in the top right when finished.
- **Upload** - Upload a new image. This button is disabled during **Edit**. It is also possible to upload multiple image files by dragging them to the designated rectangular target. After uploading a file, it switches to edit mode. When multiple files are uploaded, the last one will be edited.
- **Search** - Start a search based on the given parameters. Empty fields will be ignored for the search, in other words, all items will be matched.
- **Restart** - *Only visible in **Edit** mode.* Changes are discarded, return to **Start** mode.
- **Rename** - *Only enabled in **Edit** mode.* The file will be renamed to the current selection. If there is no change, the button is disabled.
- **Delete** - *Only enabled in **Edit** mode.* The file will be deleted.

## Search/Edit fields
- **Filename** - A partial filename to match in search mode. In edit mode, changing it will do a rename when that button is clicked. But there is an undo button to reset the filename field to the current filename.
- **Younger than** - *Only visible in **Start**/**Search** modes.* Enter a number of days, weeks or months and select which is used to filter search results based on the timestamp of the file.
- **Older than** - *Only visible in **Start**/**Search** modes.* Enter a number of days, weeks or months and select which is used to filter search results  based on the timestamp of the file.

## Search Results
- **Image** - Thumbnail of the cover image.
- **Filename** - Current name of the file.

## Editing
Editing only allows the file to be renamed. The change only takes effect once the **Rename** button is clicked.
