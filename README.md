Wiki export
===========

This plugin adds the ability to export Moodle wikis as either PDFs or epub documents.
It was commissioned by the Judicial Institute for Scotland, originally for their Totara LMS implementation, with Synergy Learning as the chosen partner, with Davo Smith providing the development work.

Many thanks to Jackie Carter - Learning Technology Manager, and the rest of the team, at the Judicial Institute for contributing this development back in to the Moodle community.

Further development of this plugin is planned for early 2015.

Usage
=====

Once the plugin is installed, you can visit a wiki, then click on the new 'Export as epub' or 'Export as PDF' links that appear
in the activity administration block (with javascript enabled, similar links are inserted on the top-right corner of the page).

Users with the 'mod/wiki:managewiki' capability also get a 'Sort pages for export' link, that allows them to choose the order in
which pages will appear in the export.

There is an additional global setting which allows a copy of any wikis on the site to be sent (as a PDF) to a given email address,
whenever they are updated (note, this will not export all wikis on the site the first time it is configured, it only sends those
that have been updated since the email address was first entered).

Customising
===========

If you want to add your organisation's logo to the front page of the exported wiki, please replace the file
local/wikiexport/pix/logo.png with your logo. Do not alter the file dimensions, it must remain 514 by 182 pixels.

Customise the following language strings, to alter the embedded export information:
'publishername' - set the PDF 'publisher' field
'printed' - set the description on the front page 'This doucment was downloaded on [date]'

(see https://docs.moodle.org/en/Language_customization for more details)

Contact
=======

Any enquiries, including custom Moodle development requests, should be sent to info@synergy-learning.com
