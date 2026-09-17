.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

The extension is configured per site, on the :guilabel:`Short URLs` tab of
the site configuration (:guilabel:`Site Management > Sites > [Edit site]`).
The settings are stored as regular site configuration values, so they can
also be edited directly in the site's :file:`config.yaml`.

..  figure:: /Images/SiteSettings.png
    :class: with-border
    :alt: The "Short URLs" tab of the site configuration, showing the page
        type selectors, the site root toggle and the automatic generation
        toggle

    The :guilabel:`Short URLs` tab of the site configuration

..  contents:: Table of contents
    :local:

.. _configuration-options:

Available settings
===================

.. confval:: shorturls_button_doktypes

   :Type: select (multiple)
   :Default: ``1`` (Standard page)

   Restricts the :guilabel:`Short URL` button in the page module toolbar to
   specific page types (:sql:`pages.doktype`). Pages of any other type show
   no button at all. Automatic generation (see
   :confval:`shorturls_auto_generate`) is not affected by this setting.

   If the setting is omitted entirely (e.g. in an older
   :file:`config.yaml` that predates this option), it behaves as if only
   the default page type (``1``, Standard page) was selected. To show the
   button for every page type again, select all available page types.

.. confval:: shorturls_button_on_siteroot

   :Type: boolean
   :Default: false

   By default, neither the :guilabel:`Short URL` button nor automatic
   generation are available on a page that has the :sql:`pages.is_siteroot`
   flag set – regardless of what :confval:`shorturls_button_doktypes` or
   :confval:`shorturls_auto_generate_doktypes` allow. Enable this setting to
   allow Short URLs on such pages as well (for example the homepage of a
   site, or the root page of a nested subsite).

.. confval:: shorturls_auto_generate

   :Type: boolean
   :Default: false

   If enabled, a short URL is automatically created whenever a **new page**
   is saved for the first time, provided its page type is allowed by
   :confval:`shorturls_auto_generate_doktypes`.

.. confval:: shorturls_auto_generate_doktypes

   :Type: select (multiple)
   :Default: ``1`` (Standard page)

   Restricts :confval:`shorturls_auto_generate` to specific page types
   (:sql:`pages.doktype`). Only visible/relevant while
   :confval:`shorturls_auto_generate` is enabled.

   If the setting is omitted entirely (e.g. in an older
   :file:`config.yaml` that predates this option), it behaves as if only
   the default page type (``1``, Standard page) was selected – matching the
   previous, hard-coded behaviour.

.. _configuration-example:

Example
=======

..  code-block:: yaml
    :caption: config/sites/<site>/config.yaml

    shorturls_button_doktypes: '1,4'
    shorturls_auto_generate: true
    shorturls_auto_generate_doktypes: '1,4'

The example above shows the manual button and enables automatic short URL
generation for Standard pages (``1``) and Shortcut pages (``4``) only.

..  note::
    When the site configuration is edited through the backend module, both
    fields are saved as a YAML list (e.g. ``['1', '4']``) rather than a
    comma-separated string. Both notations are accepted.
