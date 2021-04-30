# Chat

### Disclaimer
The software is still in development. Please report any problems to your [site administrator](mailto:{{ support }}) ({{ support }}).

## Main Chat Window
This is the starting point for chatting, but there are also links on the profile page. At the top you will see **your** avatar and display name. You can change these in your WordPress profile -- just click on the link. Consider changing your display name if you feel you want more privacy.

The **Help** button shows this help screen. Click the **x** at the top right to close the screen or just refresh.

Chatting is a new feature so expect problems. Feedback is appreciated.

## Persistence
It is possible to chat in real-time about a topic or to a given person. The messages "persist" (they are stored in the database). Each chat type shows a header with some information. New chat messages can be entered in the text box on the bottom and they will scroll up as other messages are entered. A previous chat written by you will show a red **<span style='color:#c00'>x</span>**. Clicking on this deletes the message without asking for confirmation. **Currently** the message is "**marked**" as deleted but is not removed from the database. The plan is to completely remove deleted messages after a period of time, but I am being cautious until the topic is thought out more.

## Features
Multiple chats are possible by opening up extra tabs in your browser. Messages are limited to 255 characters. HTML will be filtered out. There is very limited support for some BBCodes ([b]**bold**[/b] and [i]_italic_[/i]).

There is not (yet) any method to mark messages as seen or to notify users that they were sent any direct messages. There is also no overview of who is online and active in a chat. Refinements in future versions of the software are planned.

### Privacy
Only other users will see your messages. They will not be seen by people visiting the website. Depending on the chat topic, the people who can see your messages will be limited. In the case of direct messages, they will only be shown to you and your chat partner.

## Chat selection
* **Direct Messages** - You need to know the display name of your intended chat partner. Click in the search box and start typing. The display names of other users are shown. Selecting a user will start the chat. Direct messages are not shown to BookClub Administrators, but they are stored in the database and can be seen there by the website administration. This is why they are called "Direct Messages" and not "Private Messages". _~~I don't need to mention that you are expected to treat others with respect.~~_
* **Book** - Start typing the title of the book in the search box. Books in our database will be shown as suggestions in a drop down box. Selecting one of these books will open a discussion for that book.
* **Group chats** - Groups you have signed up for will be listed here. It is always possible to change the selection in your bookclub profile.
* **Talk about upcoming events** - Future events or events within the past three months that you were involved with (if any) will be listed. (Events that are passed are grayed out.) Events older than three months will not be suggested anymore, but it should be possible to bookmark a chat should you wish to come back to it later.
