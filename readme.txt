=== Sort/Filter Widget ===
Contributors: ardnived, ctlt-dev, ubcdev
Tags: sort, filter, search, widget, shortcode, relevanssi
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 1.0

Adds a widget that will let the user sort and filter search results.


== Usage ==
Simply drag the "Sort/Filter" widget to any widget area (In Appearance -> Widgets).
The widget will only appear when viewing search results (When is_search() evaluates to true).

This plugin includes integration for the Relevanssi, and Evaluate plugins.

The widget can also be displayed using the shortcode [sort_filter_form].
The shortcode accepts all of the widget's options at attributes.
For example [sort_filter_form enable_filter="false" metrics="1,3,7"]

The supported attributes, and their defaults are:
    autorefresh       => true
    enable_sort       => true
    enable_orderby    => true
    enable_evaluate   => true
    enable_alpha      => true
    enable_time       => true
    enable_modified   => false
    enable_relevanssi => true
    enable_order      => true
    enable_filter     => true
    enable_authors    => true
    enable_categories => true
    metrics           => "" (Accepts a comma seperated list of metric ids)
    authors_mode      => exclude (Accepts "exclude" or "include")
    authors           => "" (Accepts a comma seperated list of user ids)
    categories_mode   => exclude (Accepts "exclude" or "include")
    categories        => "" (Accepts a comma seperated list of category ids)
    allow_multiple_authors    => true
    allow_multiple_categories => true

== Installation ==

1. Extract the zip file into wp-content/plugins/ in your WordPress installation
2. Go to plugins page to activate
3. Go to Appearance -> Widgets and enable the "Sort/Filter" widget.


== Changelog ==
= 1.0 =
* Initial release
