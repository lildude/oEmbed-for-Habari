oEmbed-for-Habari
=================

** THIS PLUGIN DOES NOT WORK YET **

A Habari plugin that allows you to embed content from certain providers using their oEmbed implementation and Habari's new support for shortcodes.

Usage
-----

** Coming soon **

This will probably be something like 

[embed width=123 height=123]URL[/embed]

Supported Sites
----------------

The list of supported oEmbed providers is currently hardcoded and limited to the following list...

 * blip.tv
 * DailyMotion
 * Flickr
 * FunnyOrDie.com
 * Hulu
 * Instagram
 * Qik
 * Photobucket
 * PollDaddy
 * Revision3
 * Scribd
 * SlideShare
 * SoundCloud
 * SmugMug
 * Twitter
 * Viddler
 * Vimeo
 * YouTube (only public videos and playlists - "unlisted" and "private" videos will not embed)

 TODO
 ----

 - Implement the basic functionality of the plugin
 - Cache the returned HTML and save it somewhere for later retrieval... maybe add a new post->info field
 - Create a config page in which the following settings can be set:
 	- custom default width & height
 	- add and remove other oembed URLs


