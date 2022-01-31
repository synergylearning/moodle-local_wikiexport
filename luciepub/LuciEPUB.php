<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
    LuciEPUB - EPUB generator
    Copyright © 2012-2014  Mikael Ylikoski

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

/**
 * LuciEPUB class and functions.
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!class_exists('LuciZip')) {
    require_once('LuciZip.php');
}

/**
 * The LuciEPUB class.
 */
class LuciEPUB {
    /**
     * @var string
     */
    protected $title = 'Lucidor';
    /**
     * @var array
     */
    protected $creators = array();
    /**
     * @var null
     */
    protected $publisher = null;
    /**
     * @var array
     */
    protected $languages = array();
    /**
     * @var null
     */
    protected $rights = null;
    /**
     * @var null
     */
    protected $description = null;
    /**
     * @var null
     */
    protected $source = null;
    /**
     * @var null
     */
    protected $date = null;
    /**
     * @var null
     */
    protected $modified = null;
    /**
     * @var null
     */
    protected $uid = null;
    /**
     * @var array
     */
    protected $ids = array();
    /**
     * @var null
     */
    protected $stylesheet = null;
    /**
     * @var int
     */
    protected $spinecounter = 1;
    /**
     * @var int
     */
    protected $counter = 1;
    /**
     * @var array
     */
    protected $extra = array();

    /**
     * Generates an id.
     *
     * @param bool $spine
     *
     * @return string
     */
    protected function generate_id($spine = false) {
        if ($spine) {
            return 'luci' . $this->spinecounter++;
        } else {
            return 'icul' . $this->counter++;
        }
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function set_title($title) {
        $this->title = $title;
    }

    /**
     * Adds an author.
     *
     * @param string $name
     * @param null $fileas
     * @param null $displayseq
     */
    public function add_author($name, $fileas = null, $displayseq = null) {
        $this->add_creator($name, 'aut', $fileas, $displayseq);
    }

    /**
     * Adds a creator.
     *
     * @param string $name
     * @param null $role
     * @param null $fileas
     * @param null $displayseq
     */
    public function add_creator($name, $role = null, $fileas = null, $displayseq = null) {
        $this->creators[] = array('name' => $name,
        'role' => $role,
        'file-as' => $fileas,
        'display-seq' => $displayseq);
    }

    /**
     * Sets the publisher.
     *
     * @param string $publisher
     */
    public function set_publisher($publisher) {
        $this->publisher = $publisher;
    }

    /**
     * Adds a language.
     *
     * @param string $lang
     */
    public function add_language($lang) {
        $this->languages[] = $lang;
    }

    /**
     * Sets the rights.
     *
     * @param string $rights
     */
    public function set_rights($rights) {
        $this->rights = $rights;
    }

    /**
     * Sets the description.
     *
     * @param string $description
     */
    public function set_description($description) {
        $this->description = $description;
    }

    /**
     * Sets the source.
     *
     * @param string $src
     * @param null $type
     */
    public function set_source($src, $type = null) {
        if (!$type) {
            if (substr($src, 0, 9) == 'urn:isbn:') {
                $type = 'isbn';
            }
        }
        $this->source = array($src, $type);
    }

    /**
     * Sets the date.
     *
     * @param null $date
     */
    public function set_date($date = null) {
        if ($date) {
            $this->date = $date;
        } else {
            // ... TODO COMMENTED OUT.
            // $tz = @date('P').
            // if $tz == '+00:00'.
            // $tz = 'Z'.

            $this->date = @gmdate('Y-m-d\TH:i:s') . 'Z';
            // ... TODO COMMENTED OUT. this->date = @date(DATE_W3C).
        }
    }

    /**
     * Adds a date.
     *
     * @param null $date
     */
    public function add_date($date = null) {
        // ... TODO.
    }

    /**
     * Sets the modified attribute.
     *
     * @param null $modified
     */
    public function set_modified($modified = null) {
        if ($modified) {
            $this->modified = $modified;
        } else {
            // ... TODO COMMENTED OUT.
            // $tz = @date('P').
            // if ($tz == '+00:00').
            // $tz = 'Z'.

            $this->modified = @gmdate('Y-m-d\TH:i:s') . 'Z';
            // ... TODO COMMENTED OUT. $this->modified = @date(DATE_W3C).
        }
    }

    /**
     * Sets the uid.
     *
     * @param null $uid
     */
    public function set_uid($uid = null) {
        if ($uid) {
            $this->uid = $uid;
        } else {
            $this->uid = $this->uuid4_gen();
        }
    }

    /**
     * Adds an id.
     *
     * @param int $id
     * @param null $type
     */
    public function add_id($id, $type = null) {
        $this->ids[] = array($id, $type);
    }

    /**
     * Sets the navigation.
     *
     * @param mixed $data
     * @param null $href
     *
     * @return array
     */
    public function set_nav($data, $href = null) {
        return $this->add_item($data, 'application/xhtml+xml', $href, false,
                   null, 'nav', null, null);
    }

    /**
     * Sets the coverpage.
     *
     * @param string $filepath
     * @param string $type
     * @param null $href
     * @param false $spine
     * @param null $fallback
     * @param null $properties
     *
     * @return array
     */
    public function set_cover($filepath, $type, $href = null, $spine = false,
                  $fallback = null, $properties = null) {
        $props = 'cover-image';
        if ($properties) {
            $props .= ' ' . $properties;
        }
        return $this->add_item(null, $type, $href, $spine,
                   $fallback, $props, $filepath, null);
    }

    /**
     * Adds a spine item.
     *
     * @param string $data
     * @param null $href
     * @param null $fallback
     * @param null $properties
     *
     * @return array
     */
    public function add_spine_item(
        $data,
        $href = null,
        $fallback = null,
        $properties = null
    ) {
        return $this->add_item(
            $data,
            'application/xhtml+xml',
            $href,
            true,
            $fallback,
            $properties,
            null,
            null
        );
    }

    /**
     * Adds a file item.
     *
     * @param string $file
     * @param string $type
     * @param null $href
     * @param false $spine
     * @param null $fallback
     * @param null $properties
     *
     * @return array
     */
    public function add_item_file($file, $type, $href = null, $spine = false,
                  $fallback = null, $properties = null) {
        return $this->add_item(null, $type, $href, $spine,
                   $fallback, $properties, null, $file);
    }

    /**
     * Adds an item's file path.
     *
     * @param string $filepath
     * @param string $type
     * @param null $href
     * @param false $spine
     * @param null $fallback
     * @param null $properties
     *
     * @return array
     */
    public function add_item_filepath($filepath, $type, $href = null,
                      $spine = false,
                      $fallback = null, $properties = null) {
        return $this->add_item(null, $type, $href, $spine,
                   $fallback, $properties, $filepath, null);
    }

    /**
     * Adds an item to the ePub document.
     *
     * @param string $data
     * @param string $type
     * @param null $href
     * @param bool $spine
     * @param null $fallback
     * @param null $properties
     * @param null $filepath
     * @param null $file
     *
     * @return array
     */
    public function add_item(
        $data,
        $type,
        $href = null,
        $spine = false,
        $fallback = null,
        $properties = null,
        $filepath = null,
        $file = null
    ) {
        $id = $this->generate_id($spine);
        if (!$type) {
            $type = $this->guess_type_from_name($href);
        }
        if (!$href) {
            $href = $id;
            $ext = $this->guess_extension_from_type($type);
            if (!$ext) {
                $ext = '.fil';
            }
            $href .= $ext;
        }
        $item = array('href' => $href,
              'data' => $data,
              'file' => $file,
              'filepath' => $filepath,
              'type' => $type,
              'spine' => $spine,
              'fallback' => $fallback,
              'properties' => $properties,
              'toc' => false,
              'id' => $id);
        $this->items[] = $item;
        return $item;
    }

    /**
     * Moves the last added item first (LIFO).
     */
    public function prepend_last_item() {
        array_unshift($this->items, array_pop($this->items));
    }


    /**
     * Sets the item's table of contents.
     *
     * @param string $title
     * @param false $headers
     * @param false $continue
     */
    public function set_item_toc($title, $headers = false, $continue = false) {
        $i = count($this->items) - 1;
        $this->items[$i]['toc'] = !!($title || $headers);
        $this->items[$i]['toc-title'] = $title;
        $this->items[$i]['toc-headers'] = $headers;
        $this->items[$i]['toc-continue'] = $continue;
    }

    /**
     * Convenience function to add HTML pages.
     *
     * @param string $html
     * @param string $title
     * @param array $config
     *
     * @return mixed|string
     */
    public function add_html($html, $title, $config) {
        if ($config['tidy']) {
            $tidy = new tidy;
            $tidy->parseString($html, $config, 'utf8');
            $tidy->cleanRepair();
            $html = $tidy->html()->value;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        // ... TODO COMMENTED OUT. $html = $doc->saveXML().

        if (!$title) {
            $title = 'Untitled';
            $heads = $doc->getElementsByTagName('head');
            if ($heads) {
                $titles = $heads->item(0)->getElementsByTagName('title');
                if ($titles) {
                    $title = $titles->item(0)->nodeValue;
                }
            }
        }

        // Check images and handle <img> tags.
        $html = preg_replace_callback('~(<img [^>]*?)src=([\'"])(.+?)[\'"]~',
                      array($this, 'img_callback'), $html);

        if ($config['split']) {
            $splits = $this->split($html);
            $first = true;
            foreach ($splits as $split) {
                $this->add_spine_item($split[0], $split[1]);
                if ($config['toc']) {
                    if ($first) {
                        $this->set_item_toc($title, true, false);
                    } else {
                        $this->set_item_toc(null, true, true);
                    }
                    $first = false;
                }
            }
        } else {
            $this->add_spine_item($html);
            if ($config['toc']) {
                $this->set_item_toc($title, true);
            }
        }

        return $title;
    }

    /**
     * Callback method for images.
     *
     * @param array $matches
     *
     * @return string
     */
    protected function img_callback($matches) {
        $path = $matches[3];
        $name = $this->add_extra($path);
        return $matches[1] . ' src="' . $name . '"';
    }

    /**
     * Adds paths to extra content.
     *
     * @param string $path
     *
     * @return mixed|void
     */
    public function add_extra($path) {
        if (substr($path, 5) == 'data:') {
            return $path;
        }

        // ... TODO FIXME normalize $path
        $path = $path;

        if (array_key_exists($path, $this->extra)) {
            return $this->extra($path);
        }

        $fname = $path;
        $pos = strrpos($fname, '/');
        if ($pos !== false) {
            $fname = substr($fname, $pos + 1);
        }
        $i = '';
        do {
            $name = 'images/' . $i . $fname;
            if ($i == '') {
                $i = 1;
            } else {
                $i++;
            }
            // SYNERGY LEARNING - fixed typo.
        } while (in_array($name, $this->extra));

        $this->extra[$path] = $name;
    }

    /**
     * Includes extra content.
     */
    protected function include_extras() {
        foreach ($this->extra as $path => $nam) {
            $type = null;
            $data = @file_get_contents($path);
            if (!$data) {
                continue;
            }

            if (!$type) {
                $type = $this->guess_type_from_name($nam);
            }
            if (!$type) {
                $type = 'image/jpeg';
            }

            $this->add_item($data, $type, $nam);
        }
        $this->extra = array();
    }

    /**
     * Updates the links.
     *
     * @param mixed $linkmap
     */
    public function update_links($linkmap) {
        $this->linkmap = $linkmap;
        foreach ($this->items as &$item) {
            if ($item['type'] != 'application/xhtml+xml') {
                continue;
            }
            if (!$item['data']) {
                continue;
            }

            $item['data'] = preg_replace_callback('~(<a [^>]*?)href=([\'"])(.+?)(#.*?)?[\'"]~',
               array($this, 'link_callback'),
               $item['data']);

        }
        unset($this->linkmap);
    }

    /**
     * Allows for callbacks on links.
     *
     * @param array $matches
     *
     * @return string
     */
    public function link_callback($matches) {
        $href = $matches[3];
        $ident = null;
        if (array_key_exists(4, $matches)) {
            $ident = $matches[4];
        }

        // ... TODO COMMENTED OUT. error_log('link_callback: ' . $href . ':' . $ident).
        if (array_key_exists($href, $this->linkmap)) {
            $href = $this->linkmap[$href];
        }
        return $matches[1] . 'href="' . $href . $ident . '"';
    }

    /**
     * Generates the navigation.
     *
     * @param null $style
     * @param bool $headers
     */
    public function generate_nav($style = null, $headers = true) {
        $text = self::get_html_head($this->title, $style, false,
                    "xmlns:epub='http://www.idpf.org/2007/ops'");
        $text .= "<nav epub:type='toc' id='toc'>\n<ol/>\n</nav>\n";
        $text .= self::get_html_end();

        $doc = new DOMDocument();
        $doc->loadXML($text);
        $root = $doc->getElementsByTagName('ol');
        $root = $root->item(0);
        $ol = $root;
        $ols = array();
        $level = array(0);
        foreach ($this->items as &$item) {
            if (!$item['toc']) {
                continue;
            }

            // ... TODO COMMENTED OUT. error_log('A: ' . item['toc-title']).

            if (!$item['toc-continue']) {
                // ... TODO COMMENTED OUT.
                // while ($level[0] > 0).
                // $old = array_shift($level).
                // $ol = $ol->parentNode.
                // $ol = $ol->parentNode.
                $level = array(0);
                $ol = $root;
            }

            if ($item['toc-title']) {
                // ... TODO COMMENTED OUT.
                // if ($ol == $root).
                // $pol = $root.
                // else $item['toc-continue'] == TRUE.
                // $pol = $ol->parentNode.
                // $pol = $pol->parentNode.

                $li = $doc->createElement('li');
                $ol->appendChild($li);
                $a = $doc->createElement('a');
                $li->appendChild($a);
                $a->setAttribute('href', $item['href']);
                $txt = $doc->createTextNode($item['toc-title']);
                $a->appendChild($txt);
                if (!$item['toc-continue']) {
                    $ol = $doc->createElement('ol');
                    $li->appendChild($ol);
                    $ols[] = $ol;
                }
            }

            // Subheaders.
            $this->headers = array();
            $item['data'] = preg_replace_callback(
                '~(<h([1-6])([^>]*?))(>(.+?)</h\2>)~',
                array($this, 'head_callback'),
                $item['data']
            );

            // ... TODO COMMENTED OUT. $n = preg_match_all(
            // ... TODO COMMENTED OUT. '~<h([1-6])([^>]*?)( id=[\'"]([^\'"]+?)[\'"])?([^>]*?)>(.+?)</h\1>~',
            // ... TODO COMMENTED OUT. $item['data'],
            // ... TODO COMMENTED OUT. $matches
            // ... TODO COMMENTED OUT. ).

            if ($this->headers) {
                foreach ($this->headers as $header) {
                    // ... TODO COMMENTED OUT. error_log("match").
                    if ($header[0] < 1) {
                        $header[0] = 1;
                    }

                    while ($level[0] > $header[0]) {
                        $old = array_shift($level);
                        $ol = $ol->parentNode;
                        $ol = $ol->parentNode;
                    }

                    if ($level[0] < $header[0]) {
                        array_unshift($level, $header[0]);
                    } else {
                        // ... TODO COMMENTED OUT. (level[0] == header[0]).
                        $ol = $ol->parentNode;
                        $ol = $ol->parentNode;
                    }

                    // Create item.
                    $li = $doc->createElement('li');
                    $ol->appendChild($li);
                    $a = $doc->createElement('a');
                    $li->appendChild($a);
                    $a->setAttribute('href', $item['href'] . '#' . $header[1]);
                    $txt = $doc->createTextNode($header[2]);
                    $a->appendChild($txt);
                    $ol = $doc->createElement('ol');
                    $li->appendChild($ol);
                    $ols[] = $ol;
                }
            }
        }

        // Remove empty ol tags.
        foreach ($ols as $ol) {
            if (!$ol->hasChildNodes()) {
                $ol->parentNode->removeChild($ol);
            }
        }

        $this->set_nav($doc->saveXML());
    }

    /**
     * Callback function for the head of the document.
     * @param array $matches
     * @return mixed|string
     */
    protected function head_callback($matches) {
        $ret = $matches[0];
        $level = $matches[2];
        $atts  = $matches[3];
        $header = $matches[5];
        $header = trim(preg_replace('~<[^>]*>~', '', $header));

        $id = null;
        if (preg_match('~ id=[\'"]([^\'"]+)[\'"]~', $atts, $mes)) {
            $id = $mes[1];
        }

        if (!$id) {
            $id = 'a' . uniqid();
            $ret = $matches[1] . ' id="' . $id . '" ' . $matches[4];
        }

        // ... TODO COMMENTED OUT. error_log("match:" . $level . ":" . $id . ":" . $header . ";").
        $this->headers[] = array($level, $id, $header);
        return $ret;
    }

    /**
     * Gets the OPF.
     *
     * @return string
     */
    public function get_opf() {
        $text = "<?xml version='1.0'?>\n" .
        "<package version='3.0' xmlns='http://www.idpf.org/2007/opf' unique-identifier='pub-id'>\n";

        $text .= "<metadata xmlns:dc='http://purl.org/dc/elements/1.1/'>\n";

        if ($this->uid) {
            // Required.
            $text .= "  <dc:identifier id='pub-id'>" . $this->uid . "</dc:identifier>\n";
        }

        // ... TODO Handle different types.
        foreach ($this->ids as $id) {
            $text .= "  <dc:identifier>" . $id . "</dc:identifier>\n";
        }

        // Required.
        $text .= "  <dc:title>" . $this->title . "</dc:title>\n";

        foreach ($this->languages as $lang) {
            $text .= "  <dc:language>" . $lang . "</dc:language>\n";
        }

        foreach ($this->creators as $creator) {
            $id = $this->generate_id();
            $text .= "  <dc:creator id='" . $id . "'>" . $creator['name'] .
                "</dc:creator>\n";
            if ($creator['role']) {
                $text .= "  <meta refines='#" . $id .
                "' property='role' scheme='marc:relators'>" .
                $creator['role'] . "</meta>\n";
            }
            if ($creator['file-as']) {
                $text .= "  <meta refines='#" . $id .
                "' property='file-as' scheme='marc:relators'>" .
                $creator['file-as'] . "</meta>\n";
            }
            if ($creator['display-seq']) {
                $text .= "  <meta refines='#" . $id .
                "' property='display-seq' scheme='marc:relators'>" .
                $creator['display-seq'] . "</meta>\n";
            }
        }

        if ($this->publisher) {
            $text .= "  <dc:publisher>" . $this->publisher . "</dc:publisher>\n";
        }

        if ($this->rights) {
            $text .= "  <dc:rights>" . $this->rights . "</dc:rights>\n";
        }

        if ($this->description) {
            $text .= "  <dc:description>" . $this->description . "</dc:description>\n";
        }

        if ($this->date) {
            $text .= "  <dc:date>" . $this->date . "</dc:date>\n";
        }

        // ... TODO dc:contributor.

        // ... TODO dc:coverage.

        // ... TODO dc:format.

        // ... TODO dc:relation.

        if ($this->source) {
            $text .= "  <dc:source id='src-id'>" . $this->source[0] .
                "</dc:source>\n";
            if ($this->source[1] == 'isbn') {
                $text .= "  <meta refines='#src-id' property='identifier-type' scheme='onix:codelist5'>15</meta>\n";
            }
        }
        // ... TODO dc:subject.
        // ... TODO dc:type.

        if ($this->modified) {
            // Required.
            $text .= "  <meta property='dcterms:modified'>" . $this->modified . "</meta>\n";
        }

        // ... TODO dcterms:date.
        $text .= "</metadata>\n";

        $text .= "<manifest>\n";
        foreach ($this->items as $item) {
            $text .= "  <item id='" . $item['id'] .
            "' href='content/" . $item['href'] .
            "' media-type='" . $item['type'] . "'";
            if ($item['fallback']) {
                $text .= " fallback='" . $item['fallback'] . "'";
            }
            if ($item['properties']) {
                $text .= " properties='" . $item['properties'] . "'";
            }
            $text .= "/>\n";
        }
        $text .= "</manifest>\n";

        $text .= "<spine>\n";
        foreach ($this->items as $item) {
            if ($item['spine']) {
                $text .= "  <itemref idref='" . $item['id'] . "'/>\n";
            }
        }
        $text .= "</spine>\n";
        $text .= "</package>";

        return $text;
    }

    /**
     * Checks and sets the document attributes.
     */
    public function check() {
        if (!$this->uid) {
            $this->set_uid();
        }

        if (!$this->modified) {
            $this->set_modified();
        }

        if (!$this->languages) {
            $this->add_language('und');
        }

        $hasnav = false;
        $hastoc = false;
        foreach ($this->items as $item) {
            if ($item['properties']) {
                if (strpos($item['properties'], 'nav') !== false) {
                    $hasnav = true;
                }
            }
            if ($item['toc']) {
                $hastoc = true;
            }
        }
        if (!$hastoc) {
            foreach ($this->items as $item) {
                if ($item['spine']) {
                    $item['toc'] = 'Start';
                }
            }
        }
        if (!$hasnav) {
            $this->generate_nav();
        }
    }

    /**
     * Generates a zip file.
     *
     * @return LuciZip
     */
    public function generate() {
        $this->include_extras();
        $this->check();

        // Generate.
        $zip = new LuciZip();
        $zip->setExtraField(false);
        $zip->addFile('application/epub+zip', 'mimetype');

        $text = "<?xml version='1.0'?>\n" .
        "<container version='1.0' xmlns='urn:oasis:names:tc:opendocument:xmlns:container'>\n" .
        "  <rootfiles>\n" .
        "    <rootfile full-path='OPS/luci.opf' media-type='application/oebps-package+xml'/>\n" .
        "  </rootfiles>\n" .
        "</container>";
        $zip->addFile($text, 'META-INF/container.xml');

        $zip->addFile($this->get_opf(), 'OPS/luci.opf');

        foreach ($this->items as $item) {
            if ($item['data']) {
                $zip->addFile($item['data'], 'OPS/content/' . $item['href']);
            } else if ($item['file']) {
                $data = '';
                while (!feof($item['file'])) {
                    $data .= fread($item['file'], 8192);
                }
                fclose($item['file']);
                $zip->addFile($data, 'OPS/content/' . $item['href']);
                unset($data);
            } else if ($item['filepath']) {
                $zip->addFile(file_get_contents($item['filepath']),
                'OPS/content/' . $item['href']);
            }
        }

        $zip->finalize();
        return $zip;
    }

    /**
     * Splits document according to XPath.
     *
     * @param string $html
     * @param string $query
     *
     * @return array|array[]
     */
    public function split($html, $query = '//h1|//h2|//h3|//h4') {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $doc->encoding = 'UTF-8';
        foreach ($doc->childNodes as $ch) {
            if ($ch->nodeType == XML_PI_NODE) {
                $doc->removeChild($ch);
            }
        }

        $xpath = new DOMXPath($doc);
        $points = $xpath->query($query);
        $apoints = array();
        for ($i = 1; $i < $points->length; $i++) {
            $apoints[] = $points->item($i);
        }

        if (!$apoints) {
            return array(array($html, null));
        }
        return $this->do_split($doc, $apoints);
    }

    /**
     * Executes the split.
     *
     * @param DOMDocument $doc
     * @param DOMNodelist $points
     *
     * @return array
     */
    public function do_split($doc, $points) {
        $head = $doc->getElementsByTagName('head');
        if ($head) {
            $head = $head->item(0);
        }
        $root = $doc->documentElement;
        $splits = array($root);

        if (defined('DOMNodelist') and is_a($points, DOMNodelist)) {
            // Copy to array so that the points can be removed from the document.
            $apoints = array();
            for ($i = 0; $i < $points->length; $i++) {
                $apoints[] = $points->item($i);
            }
        } else {
            $apoints = $points;
        }

        foreach ($apoints as $point) {
            // ... TODO COMMENTED OUT. error_log("POINT: " . $point->localName).
            $current = $point;
            // Could go to first parent with previous siblings or previous non-whitespace.
            // ... TODO COMMENTED OUT. while (!$current->previousSibling).
            // ... TODO COMMENTED OUT.  $current = $current->parentNode.

            $frag = null;
            $move = true;
            while ($current->parentNode) {
                // ... TODO COMMENTED OUT. error_log("NODE: " . $current->localName).
                $parent = $current->parentNode;
                // ... TODO COMMENTED OUT. error_log("PARENT: " . $parent->localName).
                $oldfrag = $frag;
                $frag = $parent->cloneNode();
                if ($oldfrag) {
                    $frag->appendChild($oldfrag);
                }

                // Move current and all following siblings.
                if ($move) {
                    while ($current) {
                        // ... TODO COMMENTED OUT. error_log("NEXT: " . $current->nodeValue).
                        $next = $current->nextSibling;
                        if ($parent) {
                            $parent->removeChild($current);
                        }
                        $frag->appendChild($current);
                        $current = $next;
                    }
                }

                if (!$parent->parentNode) {
                    break;
                }
                if ($parent->isSameNode($root)) {
                    break;
                }

                // Continue with parents siblings.
                if ($parent->nextSibling) {
                    // ... TODO COMMENTED OUT. error_log("GO TO PARENTS SIBLING").
                    $current = $parent->nextSibling;
                    $move = true;
                } else {
                    // Or with parent.
                    // ... TODO COMMENTED OUT. error_log("GO TO PARENT").
                    $current = $parent;
                    $move = false;
                }
            }

            $root = $frag;
            $splits[] = $frag;
            $doc->replaceChild($frag, $doc->documentElement);
            // ... TODO COMMENTED OUT. error_log("i: " . $i . " length: " . $points->length).
        }

        // Make file names.
        $names = array();
        for ($i = 0; $i < count($splits); $i++) {
            $names[$i] = $this->generate_id(true) . '.html';
        }

        // Make id => doc mapping.
        $ids = array();
        $n = 0;
        foreach ($splits as $split) {
            $doc->replaceChild($split, $doc->documentElement);
            $xpath = new DOMXPath($doc);
            $entries = $xpath->query('//*[@id]|//a[@name]', $split);
            foreach ($entries as $entry) {
                if ($entry->hasAttribute('id')) {
                    // ... TODO COMMENTED OUT. error_log("ID: " . entry->getAttribute('id')).
                    $ids['#' . $entry->getAttribute('id')] = $n;
                } else {
                    // NOTE: a[name].
                    // ... TODO COMMENTED OUT. error_log("id: " . entry->getAttribute('name')).
                    $ids['#' . $entry->getAttribute('name')] = $n;
                }
            }
            $n++;
        }
        // Update internal links.
        foreach ($splits as $split) {
            $doc->replaceChild($split, $doc->documentElement);
            $xpath = new DOMXPath($doc);
            $entries = $xpath->query('//a[@href]', $split);
            foreach ($entries as $entry) {
                if ($entry->hasAttribute('href')) {
                    $href = $entry->getAttribute('href');
                    if ($href[0] == '#' and array_key_exists($href, $ids)) {
                        // ... TODO COMMENTED OUT. error_log("href: " . $href).
                        $entry->setAttribute('href',
                        $names[$ids[$href]] . $href);
                    }
                }
            }
        }

        // Generate split docs.
        $docs = array();
        $i = 0;
        foreach ($splits as $split) {
            $doc->replaceChild($split, $doc->documentElement);
            if ($head) {
                if ($doc->documentElement->hasChildNodes()) {
                    if (!$head->isSameNode($doc->documentElement->firstChild)) {
                        $doc->documentElement->insertBefore($head, $doc->documentElement->firstChild);
                    }
                } else {
                    $doc->documentElement->appendChild($head);
                }
            }
            $docs[] = array($doc->saveXML(), $names[$i]);
            $i++;
        }
        return $docs;
    }

    /**
     * Gets the HTML head.
     *
     * @param string $title
     * @param bool $style
     * @param bool $bodyclass
     * @param string $namespaces
     *
     * @return string
     */
    public static function get_html_head($title, $style = false, $bodyclass = false, $namespaces = '') {
        if ($namespaces) {
            $namespaces = ' ' . $namespaces;
        }
        $text = "<?xml version='1.0' encoding='UTF-8'?>\n<!DOCTYPE html>\n" .
        "<html xmlns='http://www.w3.org/1999/xhtml'" . $namespaces . ">\n" .
        "<head>\n<title>" . self::escape($title) . "</title>\n";
        if ($style) {
            $text .= "<link href='" . self::escape($style) .
            "' rel='stylesheet' type='text/css'/>\n";
        }
        $text .= "</head>\n";
        if ($bodyclass) {
            $text .= "<body class='" . $bodyclass . "'>\n";
        } else {
            $text .= "<body>\n";
        }
        return $text;
    }

    /**
     * Gets the end of the HTML document.
     *
     * @return string
     */
    public static function get_html_end() {
        return "</body>\n</html>";
    }

    /**
     * Wraps the HTML documents.
     *
     * @param string $content
     * @param string $title
     * @param false $style
     * @param false $bodyclass
     *
     * @return string
     */
    public static function get_html_wrap($content, $title, $style = false, $bodyclass = false) {
        return self::get_html_head($title, $style, $bodyclass) . $content . self::get_html_end();
    }

    /**
     * Gets the HTML coverpage.
     *
     * @param string $title
     * @param null $subtitle
     * @param null $bottom
     * @param null $image
     * @param null $style
     *
     * @return string
     */
    public static function get_html_cover($title, $subtitle = null, $bottom = null, $image = null, $style = null) {
        $html = "<br/><br/><br/><br/>\n";
        $html .= "<div style='text-align: center'>\n";
        $html .= "<h2>" . $title . "</h2>\n";
        if ($subtitle) {
            $html .= "<h4>" . $subtitle . "</h4>\n";
        }
        if ($image) {
            $html .= "<p><img src='" . $image . "'/></p>\n";
        } else {
            $html .= "<br/>\n";
        }
        if ($bottom) {
            $html .= "<p>" . $bottom . "</p>\n";
        }
        $html .= "<br/><br/><br/>\n";
        $html .= "</div>\n";
        return self::get_html_wrap($html, $title, $style);
    }

    /**
     * Generate uuid version 4
     *
     * From http://www.ajaxray.com/blog/2008/02/06/php-uuid-generator-function/comment-page-1/#comment-2667.
     * It can be initialized with: mt_srand(intval(microtime(TRUE) * 1000)).
     * An alternative would be uuid_create() from the PECL uuid extension.
     *
     * @return string
     */
    public static function uuid4_gen() {
        $b = md5(uniqid(mt_rand(), true), true);
        $b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3F) | 0x80);
        return implode("-", unpack("H8a/H4b/H4c/H4d/H12e", $b));
    }

    /**
     * Escapes special html characters.
     *
     * @param string $data
     *
     * @return string
     */
    public static function escape($data) {
        return htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    }

    /**
     * @var string[]
     */
    protected static $suffixtype =
    array('.3gp' => 'video/3gpp',
          '.avi' => 'video/x-msvideo',
          '.css' => 'text/css',
          '.gif' => 'image/gif',
          '.html' => 'application/xhtml+xml',
          '.jpeg' => 'image/jpeg',
          '.jpg' => 'image/jpeg',
          '.js' => 'text/javascript',
          '.m4a' => 'audio/mpeg',
          '.mp3' => 'audio/mpeg',
          '.mp4' => 'video/mp4',
          '.mpg' => 'video/mpeg',
          '.oga' => 'audio/ogg',
          '.ogg' => 'audio/ogg',
          '.ogm' => 'video/ogg',
          '.ogv' => 'video/ogg',
          '.otf' => 'application/vnd.ms-opentype',
          '.png' => 'image/png',
          '.spx' => 'audio/ogg',
          '.svg' => 'image/svg+xml',
          '.swf' => 'application-x-shockwave-flash',
          '.ttf' => 'application/x-font-ttf',
          '.txt' => 'text/plain',
          '.wav' => 'audio/x-wav',
          '.webm' => 'video/webm',
          '.wma' => 'audio/x-ms-wma',
          '.woff' => 'application/font-woff');

    /**
     * Guesses file types from file suffix.
     *
     * @param string $name
     *
     * @return string|null
     */
    public static function guess_type_from_name($name) {
        if (!$name) {
            return null;
        }
        $suffix = '';
        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $suffix = strtolower(substr($name, $pos));
        }
        if (array_key_exists($suffix, self::$suffixtype)) {
            return self::$suffixtype[$suffix];
        }
        return null;
    }

    /**
     * Guesses file types from file extension.
     *
     * @param string $type
     *
     * @return false|int|string|null
     */
    public static function guess_extension_from_type($type) {
        if (!$type) {
            return null;
        }
        return array_search($type, self::$suffixtype);
    }
}
