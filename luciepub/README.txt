These files are copied (unmodified) from version 0.10 of the
Lucimoo EPUB import/export add-ons for the Moodle book module.

The original README from those plugins is reproduced below:

--------------------------------------------------------------

Lucimoo EPUB import/export add-ons for the Moodle book module.

Lucimoo consists of two add-ons for the Moodle book module:
* The "importepub" add-on provides functionality to import
material from EPUB ebooks into book module books.
* The "exportepub" add-on provides functionality to export
book module books as EPUB ebooks.


Requirements:

The book module is included in Moodle 2.3 and later, and by
default these add-ons can only be installed in these versions
of Moodle. If you use Moodle 2.0-2.2 and have manually installed
the book module and want to use these add-ons, you must remove
the line "$plugin->requires = 2012062500;" from the files
"importepub/version.php" and "exportepub/version.php" before
they can be installed.


Installation:

The import and export add-ons can be installed independently
of each other, so if you only want one of them you do not
need to install the other.

In Moodle 2.5 and later you can install add-ons from the
"Site administration" view. In older versions of Moodle
you need to install them manually.

* Installation from the "Site administration" view
  (possible with Moodle 2.5 and later):

1. Login as admin and visit the Moodle
   "Site administration" view, and click on
   "Site administration" > "Plugins" > "Install add-ons"
   on the left.

2. Choose "Book / Book tool (booktool)" as the Plugin type.

3. Select the Lucimoo ZIP package you want to install.

4. Check the "Acknowledgement" box.

5. Click on the "Install add-on from the ZIP file" button.

6. Click on the "Install add-on!" button.

7. Repeat 1-6 with the other Lucimoo ZIP package if you
   want to install both the import and the export add-ons.

* Manual installation (possible with Moodle 2.0 and later):

1. Unzip the Lucimoo ZIP package(s) to get the folder(s)
   "importepub" and/or "exportepub".

2. Upload or copy the "importepub" and/or "exportepub"
   folder(s) into the "mod/book/tool/" folder of your
   Moodle installation.

3. Login as admin and visit the Moodle
   "Site administration" view, and click on
   "Site administration" > "Notifications" on the left
   and follow the instructions to finish the installation.

General add-on installation instructions are available at
http://docs.moodle.org/26/en/Installing_add-ons

* Upgrading from an older version of Lucimoo to a newer version:

The Lucimoo add-ons do not store any add-on specific data in the
Moodle database. This means that you do not lose any data if you
uninstall them, and you can upgrade to another version of the
Lucimoo add-ons simply by uninstalling the old version and then
install the new version.


Configuration:

The export add-on has a few settings that can be changed by
editing the file 'config.php'.


Usage:

* Exporting a book as an EPUB ebook:

1. Display the book you want to export.

2. Click on the "Download as ebook" link under
   "Settings" > "Book administration" on the left.

* Importing chapters from an EPUB ebook into an existing book:

1. Display the book you want to import chapters into.

2. Click on the "Turn editing on" link under
   "Settings" > "Book administration" on the left.

3. Click on the "Import chapters from ebook" link under
   "Settings" > "Book administration" on the left.

4. Select the EPUB file and click on "Import".

* Create new books from EPUB ebooks:

1. Click on the "Turn editing on" link under
   "Settings" > "Book administration" on the left.

2. Create a new (temporary) book in the section where
   you want to import the new book(s). To do this you
   click on the "Add an activity or resource" link and
   select "Book", and then fill in a title etc.

3. Display the temporary book. (As the book is empty,
   an editing form will be displayed.)

4. Click on the "Import ebook as new book" link under
   "Settings" > "Book administration" on the left.

5. Either:

   a) Select the EPUB file(s) you want to import,
      and click on "Import".

   or:

   b) Enter URL:s for the EPUB file(s) you want to import,
      one on each line in the textbox, and click on
      "Import from URL:s".

6. Delete the temporary book you created in step 1.

   (Steps 2 and 6 are only necessary if the section
   does not already contain any books. You must display
   a book to see the "Import ebook as new book" link,
   but it can be any book. Unfortunately Moodle does
   not provide any better place to put such a link.)


Credits:

The Lucimoo EPUB import add-on includes code from the
following external project:

PHP-CSS-Parser
Copyright (c) 2011 Raphael Schweikert, http://sabberworm.com/
https://github.com/sabberworm/PHP-CSS-Parser

The Lucimoo EPUB export add-on includes code from the
following external project:

PHPZip
Copyright 2009-2012 A. Grandt
https://github.com/Grandt/PHPZip


Contact information:

Web site: http://lucidor.org/lucimoo/
