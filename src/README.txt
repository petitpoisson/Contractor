Contractor

Contractor is a tiny CRM component for Joomla! 

Requirements:
- Joomla 5.0+ (although it might run with Joomla 4.1+)
- PHP 7.1-8.5 (Joomla! 5.0 requires PHP 8.1+)
- MySQL 5.6+ / MariaDB 10.1+

The package includes a component, which is the main part of the tool. The component includes a frontend view for the clients to see their data (contracts/subscriptions, and invoices). It also includes a module which displays the same information but could be more practical in certain use cases. Finally, it includes two plugins : a shortcut icon for the Joomla! admin dashboard, and a task scheduler plugin. The installer creates a scheduled task upon installation, which allows for sending warnings to the admin about expiring/expired contracts. 

There is no manual or instructions as of now (this was first developed as an internal tool, so I don't have a lot of time to cope with instructions). Any inquiry, bug report, security report, feature request, or maybe thanks, who knows, should be sent to xavier@petitpoisson.be. 

Contractor is made by Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
Copyright (C) 2026 Xavier Spirlet. All rights reserved.
Licensed under GNU General Public License version 3 or later; see LICENSE.txt

Includes Bootstrap v5.3.8, licensed under MIT License (https://github.com/twbs/bootstrap/blob/main/LICENSE)
Includes UIkit v3.25.27, Copyright (c) 2013-2020 YOOtheme GmbH (getuikit.com), licensed under MIT License (https://github.com/uikit/uikit/blob/develop/LICENSE.md)
Embeds Quill v2.0.3, licensed under BSD License (https://quilljs.com/)

This component includes a frontend module, a quickicon plugin for admin dashboard and a task scheduler plugin for automating warning emails. All embedded extensions need the main component to work. 