Wiki export
===========

This plugin adds the ability to export Moodle wikis as either PDFs or epub documents.
It was developed by Davo Smith of Synergy Learning, on behalf of the Judicial Institute for Scotland

Important note
==============

To allow for sorting of the exported pages an extra field 'sortorder' is added to the existing 'wiki_pages' database table.
This should cause no problems on your Moodle site, but you should be aware that this will happen.

Usage
=====

Once the plugin is installed, you can visit a wiki, then click on the new 'Export as epub' or 'Export as PDF' links that appear
in the activity administration block (with javascript enabled, similar links are inserted on the top-right corner of the page).

Users with the 'mod/wiki:managewiki' capability also get a 'Sort pages for export' link, that allows them to choose the order in
which pages will appear in the export.

There is an additional, global setting which allows

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
