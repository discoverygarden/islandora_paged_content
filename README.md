# Islandora Paged Content

## Introduction

Libraries for paged content in Islandora.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/discoverygarden/islandora)
* [Tuque](https://github.com/islandora/tuque)

This module has the following optional requirements:
* [Ghostscript](https://www.ghostscript.com/) - Debian/Ubuntu `sudo apt-get
install ghostscript`
* [pdftotext](http://poppler.freedesktop.org) - Debian/Ubuntu `sudo apt-get
install poppler-utils`
* [pdfinfo](http://poppler.freedesktop.org) -  Debian/Ubuntu `sudo apt-get
install poppler-utils`

## Installation

Install as
[usual](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## Configuration

Set the path for `gs` (GhostScript), the externally accessible Djatoka URL, and
the 'Solr page sequence number field' in Configuration » Islandora » Solution
pack configuration » Paged Content Module
(admin/confg/islandora/solution_pack_config/paged_content).

![Configuration](https://user-images.githubusercontent.com/2857697/39014759-e2ef9c1e-43e0-11e8-921c-c2a3234d65d2.jpg)

There is an option to set the page label to the page's sequence number. On
ingest, each page's label will be set to its sequence number. When reordering
pages, all of the page labels will be updated with the new sequence numbers.

You can also "Hide Page Objects From Search Results", so that only the parent
object is returned.  If you use this option, make sure that you check the
"Aggregate OCR?" box when ingesting your paged content object. Otherwise, the
parent object will not receive an OCR datastream, and will not be returned in
search results.

## Documentation

Further documentation for this module is available at [our
wiki](https://wiki.duraspace.org/display/ISLANDORA/Islandora+Paged+Content).

### Drush scripts

`paged-content-consolidate-missing-ocr`

This drush command finds all page objects whose parent does not have a
OCR datastream, generates it by combining the OCR datastreams from the children
and adds that datastream to the parent.


## Troubleshooting/Issues

Having problems or solved one? Create an issue, check out the Islandora Google
groups.

* [Users](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Devs](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

or contact [discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out the helpful
[Documentation](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers),
[Developers](http://islandora.ca/developers) section on Islandora.ca and create
an issue, pull request and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
