/*
 * jQuery TTF Text Rendering Plugin for converting elements into ttf rendered images
 *
 * Copyright (C) 2009 Mike Dabbs and Vinny Troia (www.curvve.com)
 *
 * Version 1.10
 *
 * This file is part of TTFGen.
 *
 * TTFGen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * TTFGen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with TtfGen.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Many thanks go to authors of code that we're used or derived from
 * to create TTFGen.
 */    
;(function($) {

// This var should point to the URL of the ttfgen.php script on your server	
var ttfgen_php_url = '/wp-content/plugins/wp-ttfgen/scripts/ttfgen.php';	
	
/**
 * Chainable method for converting elements into ttf rendered images.
 *
 * @param options
 * @param callback fn invoked for each matched element before conversion
 * @param callback fn invoked for each matched element after conversion
 */
$.fn.ttfgen = function(options, f1, f2) {
	
    if (typeof options == 'function') {
        f2 = f1;
        f1 = options;
        options = {};
    }
    
    return this.each(function() {

        var o = getSettings(this, options);

        // pre-conversion callback, passes original element and fully populated options
        if (typeof f1 == 'function') f1(this, o);

        // save any  existing id and class
        var id = this.id ? (' id="'+this.id+'"') : '';
        var cls = o.cls ? (' class="' + o.cls + '"') : '';

        urltext = encodeURIComponent(o.caption);
        urltext = urltext.replace(/'/g, "%27").replace(/#/g, "%35").replace(/\(/g, "%28").replace(/\)/g, "%29");
        
        // build query string
		var q = '?text=' + urltext;
		q = q + '&font=' + o.font;
		q = q + '&fsize=' + o.fsize;
		q = q + '&pos=' + o.pos;
		q = q + '&leading=' + o.leading;
		if (o.margin)
			q = q + '&margin=' + encodeURIComponent(o.margin);
		if (o.trans)
			q = q + '&trans=1';
		if (o.width && o.width != 'auto')
			q = q + '&width=' + o.width;
		if (o.lineHeight)
			q = q + '&lineHeight=' + o.lineHeight;
		if (o.textTransform)
			q = q + '&transform=' + o.textTransform;
		
		// rgb must be last since we munge it depending on background stuff
		q = q + '&rgb=' + encodeURIComponent(o.bgColor) + ',' + encodeURIComponent(o.fgColor);

		// Build new img element
        var imgt = '<img' + id + cls;
        $.each(o.attrs, function(i, p) {
        	imgt += ' ' + i + '="' + p + '"';
        });
        
        if (this.alt && this.alt != '')
        	imgt += ' alt="' + this.alt.replace(/"/g, '&quot;') + '"';
        else if (o.addAlt)
        	imgt += ' alt="' + o.caption.replace(/"/g, '&quot;').replace(/\|/g, " ") + '"';
        
        if (this.title && this.title != '')
        	imgt += ' title="' + this.title.replace(/"/g, '&quot;') + '"';
        else if (o.addTitle)
        	imgt += ' title="' + o.caption.replace(/"/g, '&quot;').replace(/\|/g, " ") + '"';
        
        if (o.asBackground) {
        	// If being rendered as a background, set style, highlight colors and mouse event handlers
        	var q2 = q;
    		if (o.hiBgColor)
    			q2 = q2 + ',' + encodeURIComponent(o.hiBgColor);
    		if (o.hiFgColor)
    			q2 = q2 + ',' + encodeURIComponent(o.hiFgColor);
        	imgt += ' style="background: transparent url(' + o.src + q2 + ') no-repeat scroll 0 0"';
   			q = q + '&blank=1';
        	imgt += ' onmouseover="this.style.backgroundPosition=\'0% 100%\'" onmouseout="this.style.backgroundPosition=\'0% 0%\'"';
        }
        imgt += '>';
        
		// Create new img element
        var i = $(imgt);

        // Set img src
	    i.attr('src', o.src + q);

	    // Replace existing element with new img elements
        $(this).after(i).remove();

        // post-conversion callback, passes original element, new div element and fully populated options
        if (typeof f2 == 'function') f2(this, i[0], o);

    });
};

// global defaults; override as needed
$.fn.ttfgen.defaults = {
    font:			'arial',		// font name
    fsize:			24,				// font size in pixels
    pos:			0,				// alignment 0=left, 1=right, 2=center
    trans:			1,				// transparent background 1=yes, 0=no
    leading:		0,				// number of space between lines (in pixels)
    asBackground:	false,			// render as css background rollover image
    bgColor:		'#ffffff',		// background color
    fgColor:		'#000000',		// text color
    attrs:			{},				// extra html attributes to add to the generated html img element
    src:			ttfgen_php_url,	// The source URL of the ttfgen.php script (from var above)
    addAlt:			true,			// add an alt attribute that contains the original text
    addTitle:		true			// add a title attribute that contains the original text
};


//
//  everything below here is private
//

function pixelsFromCssValue(cssValue, element) 
{
	if (cssValue != 'normal' && cssValue != 'auto') {
		var matches = undefined;
		if (matches = cssValue.match(/([\-\d+\.]+)px/)) {
			return matches[1];
		} 
		else {
			// thanks to Dean Edwards for this very sneaky way to get IE to convert 
			// relative values to pixel values
			
			var pixelAmount;
			
			var leftInlineStyle = element.style.left;
			var leftRuntimeStyle = element.runtimeStyle.left;
	
			element.runtimeStyle.left = element.currentStyle.left;
			if (cssValue.match(/\d(em|%)$/)) {
				element.style.left = '1em';
			} else {
				element.style.left = cssValue || 0;
			}
	
			pixelAmount = element.style.pixelLeft;
		
			element.style.left = leftInlineStyle;
			element.runtimeStyle.left = leftRuntimeStyle;
			
			if (pixelAmount) 
				return pixelAmount;
		}
	}
	return undefined;
}

function dec2hex(v)
{
	var chars = '0123456789ABCDEF';
	var i = parseInt(v);
	
	return chars.substr(i/16, 1) + chars.substr(i & 15, 1);
}

function getColor(c)
{
	if (matches = c.match(/^rgb\(\s*(\d+),\s*(\d+),\s*(\d+)\s*\)$/)) {
		return '#' + dec2hex(matches[1]) + dec2hex(matches[2]) + dec2hex(matches[3]);
	}
	if (matches = c.match(/^rgba\(\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)\s*\)$/)) {
		if (dec2hex(matches[4]) == '00')
			return 'transparent';
		return '#' + dec2hex(matches[1]) + dec2hex(matches[2]) + dec2hex(matches[3]);
	}
	return c;
}

// flatten all possible options: global defaults, meta, option obj
function getSettings(el, options) 
{
    options = options || {};
    var $el = $(el);
    var cls = el.className || '';

    // support metadata plugin (v1.0 and v2.0)
    var meta = $.metadata ? $el.metadata() : $.meta ? $el.data() : {};
    meta = meta || {};
    var w = meta.width  || parseInt(((cls.match(/w:(\d+)/)||[])[1]||0));
    var h = meta.height || parseInt(((cls.match(/h:(\d+)/)||[])[1]||0));

    if (w) meta.width  = w;
    if (h) meta.height = h;
    if (cls) meta.cls = cls;
    
    var style = window.getComputedStyle? window.getComputedStyle(el, '') : el.currentStyle;

    // Pull in the css values
    var cssOptions = {};
    var v;
    v = pixelsFromCssValue(style.fontSize, el);
    if (v != undefined) cssOptions.fsize = v;
    v = getColor(style.color);
    if (v != undefined) cssOptions.fgColor = v;
    v = pixelsFromCssValue(style.letterSpacing, el);
    if (v != undefined) cssOptions.leading = v;
    v = getColor(style.backgroundColor);
    if (v != undefined) {
    	if (v == 'transparent')
    		cssOptions.trans = true;
    	else {
    		cssOptions.bgColor = v;
    		cssOptions.trans = false;
    	}
    }
    v = style.fontFamily;
    if (v != 'undefined')
    	cssOptions.font = v.split(/\s*,\s*/)[0].replace(/(^"|^'|'$|"$)/g, '');
    v = pixelsFromCssValue(style.width, el);
	if (v != undefined) { cssOptions.width = v; }
	if (style.textAlign == 'right')
    	cssOptions.pos = 1;
    else if (style.textAlign == 'center')
    	cssOptions.pos = 2;
    v = pixelsFromCssValue(style.lineHeight, el);
    if (v != undefined) cssOptions.lineHeight = v;
    if (style.textTransform) {
	    if (style.textTransform.match(/[Uu]ppercase/))
	        cssOptions.textTransform = 'uppercase';
	    else if (style.textTransform.match(/[Ll]owercase/))
	        cssOptions.textTransform = 'lowercase';
	    else if (style.textTransform.match(/[Cc]apitalize/))
	        cssOptions.textTransform = 'capitalize';
    }
    
    var a = $.fn.ttfgen.defaults;
    var b = options;
    var c = meta;
    
    if (b.bgColor && b.trans == 'undefined') b.trans = false;
    if (c.bgColor && c.trans == 'undefined') c.trans = false;

    var opts = $.extend({}, a, cssOptions, b, c);
    $.each(['attrs'], function(i,o) {
        opts[o] = $.extend({}, a[o] || {}, b[o] || {}, c[o] || {});
    });

    if (typeof opts.caption == 'undefined') opts.caption = $el.text();
    
    // make sure we have a source!
    opts.src = opts.src || $el.attr('href') || $el.attr('src') || 'unknown';
    return opts;
};


})(jQuery);

