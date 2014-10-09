YUI.add('moodle-local_wikiexport-printlinks', function (Y, NAME) {

/*global M*/
M.local_wikiexport = M.local_wikiexport || {};
M.local_wikiexport.printlinks = {

    init: function(links) {
        var el, parent, i;

        // Find the right place in the DOM to add the links.
        try {
            el = Y.one('#intro');
            el = el.next('.wiki_right');
            if (el.next('.wiki_right')) {
                el = el.next('.wiki_right');
            }
            el = el.next();
            parent = el.ancestor();
        } catch (e) {
            return; // The correct location to add the links was not found.
        }

        for (i in links) {
            if (!links.hasOwnProperty(i)) {
                continue;
            }
            parent.insert(links[i], el);
        }
    }
};

}, '@VERSION@', {"requires": ["base", "node"]});
