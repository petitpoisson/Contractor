Contractor - CHANGELOG

1.1.1
- Further plugin enabling (in parallel with plugins development)

1.1.0
- Changed the display for Clients list (more compact)
- Fixed the fact that 2 clients could be linked to the same Joomla user ID
- Now when a Joomla user is deleted, the client is updated upon next save and not linked to that account anymore
- Fixed the fact that it was impossible to select "No user" as linked joomla user for a client
- Clients and contracts can now have multiple tags
- Tags now display with correct colors for clients and contracts (and selectors and filters work better)
- Fixed the dates in contract view, they tended to change on saving
- Fixed incorrect number of contract lines and contract total price in the contracts list view
- (tried to) prevent password managers to jump in on the client edition page
- Allowed multiple e-mails in client edition
- Cleared form validation in client edition
- Changed display for contracts list
- Last reminder message date is now correctly updated upon message sending
- Last reminder date is now displayed in contracts list
- More language files bug squashing
- Added "+1 year" button to contract valid_thru date
- Prepared hooks to allow for plugins


1.0.2
- Multiple language files corrections
- Services list in mail to client : allowed multi-lines services and corrected vertical alignment of prices
- plugin task/contractorwarning : bugfix, namespace declaration not first line in code
- Backend "Tags" view : fixed could not delete tags
- Added duplicate, save as copy, save and new to Clients and Contracts views
- Changed editor height for signature (was too small for editor toolbars)
- Fixed a bug where it was impossible to create invoices for clients
- Added alerts for unsaved modification in client view
- Grouped some "Save" buttons in toolbars 'Client, Contract'
- Added inline editing for dates and references of invoices in clent view
- Mended the sort order of the invoices (most recent always at top even after inline editing)
- Removed Quill as the editor for communication prefs (regretfully) and reverted to default editor
- Fixed relative paths in e-mails and e-mail preview
- Added a test send for the expiration message

1.0.1
Bugfix, Joomla 6.1.2 compatibility

1.0.0
First release version