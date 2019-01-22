Fetch content from REST API with Drupal 8 Http Client Service &amp; Block API.

## Dependencies
The module does not have any dependencies.

## Installation
Install through the admin UI `/admin/modules` or drupal-console `drupal module:install icndb_block`.

## Usage
A block (admin label: ICNDb: Random Jokes) is provided with the module. You can configure the block with various provided settings.

## Configuration settings
- **Quantity:** (number) The number of jokes to be shown. Defaults to a single item.
- **Rename main character:** The ICNDb API allows us to rename the main character (defaults to Chuck Norris).
  + **enable renaming:** (checkbox) Allows you to enable or disable this functionality.
  + **first name:** (text) First name for the main character. e.g. John.
  + **last name:** (text) Last name for the main character. e.g. Smith.
- **Escape special characters:** Some jokes contain some special characters such as “, & or <. These special characters can make it hard to show the joke correctly or can even ruin the syntax of the result. To fix this, the API escapes special characters before returning the result. There are two options: HTML encoding or JavaScript encoding.

HTML encoding is the default. In this case, &, ” (double quotes), < and > are encoded in their respective HTML format (e.g., &amp;). In this case, you can directly insert the resulting joke in an HTML page without errors.

With JavaScript encoding, only quotes (both double and single) are escaped. In this case, backslashes are added (e.g., “Chuck’s fist” becomes “Chuck\’s fist”).
  + **enable escaping:** (checkbox) Allows you to enable or disable this functionality.
  + **escape format:** (select) Let's you choose escaping format (read above).
- **Filter results by categories:** The jokes in the database are given categories such as 'nerdy or 'explicit'. When fetching multiple jokes, it is possible to limit the scope to some of these categories or exclude some of them.
  + **enable filtering:** (checkbox) Allows you to enable or disable this functionality.
  + **filtering type:** (select) Whether to include jokes from a certain category (inclusive) or to exclude them (exclusive).
  + **include/exclude results from:** (select) The categories to be included or to be excluded. This setting is depended on the filtering type selection.
  
> [Read more at ICNDb API docs.](http://www.icndb.com/api/)
