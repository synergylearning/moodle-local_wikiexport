YUI.add('moodle-local_wikiexport-sortpages', function (Y, NAME) {

/*global M*/
M.local_wikiexport = M.local_wikiexport || {};
M.local_wikiexport.sortpages = {
    cmid: null,
    groupid: null,
    userid: null,
    sesskey: null,

    init: function(opts) {
        var pages, sortpages, goingup = false, lasty = 0, destsort = null, newsort, maxsort = 0;

        this.cmid = opts.cmid;
        this.sesskey = opts.sesskey;
        this.groupid = opts.groupid;
        this.userid = opts.userid;

        sortpages = Y.one('#wiki-sortpages');
        pages = sortpages.all('li');
        pages.each(function(el) {
            var dd, sortorder;

            if (el.hasClass('nomove')) {
                return;
            }

            dd = new Y.DD.Drag({
                node: el,
                target: {
                    padding: '0 0 0 20'
                }
            }).addHandle(el.one('.jsicons')).plug(Y.Plugin.DDProxy, {
                    moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                    constrain2node: sortpages,
                    constrainX: true
            });

            sortorder = parseInt(this.get_sortorder(el), 10);
            if (sortorder > maxsort) {
                maxsort = sortorder;
            }

        }, this);

        Y.DD.DDM.on('drag:start', function(e) {
            var drag = e.target;
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5'
            });
            destsort = null;
        });

        Y.DD.DDM.on('drag:end', function(e) {
            var drag = e.target;
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });

            if (destsort) {
                this.send_reorder(drag.get('node'), destsort);
            }
        }, this);

        Y.DD.DDM.on('drag:drag', function(e) {
            var y = e.target.lastXY[1];
            goingup = (y < lasty);
            lasty = y;
        });

        Y.DD.DDM.on('drop:over', function(e) {
            var drag = e.drag.get('node'),
                drop = e.drop.get('node');

            var oldsort = parseInt(this.get_sortorder(drag), 10);

            if (drop.get('tagName').toLowerCase() === 'li') {
                if (!goingup) {
                    drop = drop.get('nextSibling');
                }

                newsort = this.get_sortorder(drop);
                if (newsort) {
                    newsort = parseInt(newsort, 10);
                    if (newsort > oldsort) {
                        newsort -= 1;
                    }
                    destsort = newsort;
                } else {
                    destsort = maxsort; // Off the end of the list.
                }

                e.drop.get('node').get('parentNode').insertBefore(drag, drop);
                e.drop.sizeShim();
            }
        }, this);
    },

    get_sortorder: function(el) {
        var classname, sortorder;

        if (!el) {
            return null;
        }
        classname = el.get('className');
        sortorder = classname.match(/sortorder-(\d+)/);
        if (sortorder) {
            return sortorder[1];
        }
        return null;
    },

    get_pageid: function(el) {
        var elid, pageid;

        elid = el.get('id');
        pageid = elid.match(/wikipageid-(\d+)/);
        if (pageid) {
            return pageid[1];
        }
        return null;
    },

    get_by_pageid: function(pageid) {
        return Y.one('#wikipageid-'+pageid);
    },

    set_sortorder: function(el, sortorder) {
        var oldsortorder;

        oldsortorder = this.get_sortorder(el);
        if (oldsortorder) {
            el.removeClass('sortorder-'+oldsortorder);
            el.addClass('sortorder-'+sortorder);
        }
    },

    show_spinner: function() {
        Y.one('#wiki-sortpages-spinner').addClass('show');
    },

    hide_spinner: function() {
        Y.one('#wiki-sortpages-spinner').removeClass('show');
    },

    send_reorder: function(drag, destsort) {
        var url, params;

        this.show_spinner();

        url = M.cfg.wwwroot + '/local/wikiexport/sortpages_ajax.php';
        params = {
            data: {
                id: this.cmid,
                userid: this.userid,
                groupid: this.groupid,
                sesskey: this.sesskey,
                action: 'moveto',
                pageid: this.get_pageid(drag),
                position: destsort
            },
            context: this,
            on: {
                success: this.update_sortorder
            }
        };

        Y.io(url, params);
    },

    update_sortorder: function(id, resp) {
        var order, pageid, el, sortorder;

        this.hide_spinner();

        resp = Y.JSON.parse(resp.responseText);
        if (resp.error) {
            alert(resp.error);
            return;
        }

        order = resp.order;
        for (pageid in order) {
            if (!order.hasOwnProperty(pageid)) {
                continue;
            }
            sortorder = order[pageid];
            el = this.get_by_pageid(pageid);
            if (el) {
                this.set_sortorder(el, sortorder);
            }
        }
    }
};

}, '@VERSION@', {"requires": ["base", "node", "io-base", "dd-constrain", "dd-proxy", "dd-drop", "json-parse"]});
