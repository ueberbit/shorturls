.. include:: /Includes.rst.txt

.. _editor:

======
Editor
======

.. _editor-button:

The Short URL button
=====================

While viewing a page in the :guilabel:`Web > Page` module, a
:guilabel:`Short URL` button is available in the top button bar for
configurable page types (see :confval:`shorturls_button_doktypes`; by
default, only Standard pages).

..  contents:: Table of contents
    :local:

.. _editor-create:

Creating a short URL
---------------------

If no short URL exists yet for the current page, the button reads
:guilabel:`Create Short URL`.

..  figure:: /Images/ButtonCreate.png
    :class: with-border
    :alt: Page module toolbar showing the "Create Short URL" button

    The button before a short URL has been created for this page

Clicking it asks for confirmation and then creates the short URL. A flash
message confirms that it has been created, and the button is replaced by
the new short URL with a clipboard button next to it.

..  figure:: /Images/ButtonCreated.png
    :class: with-border
    :alt: Page module after a short URL has been created, showing a success
        message and the short URL with a clipboard button

    After creation, the short URL is shown (here ``/hmRiKgfS``)

.. _editor-copy:

Copying an existing short URL
------------------------------

If a short URL already exists for the page, its path (e.g. ``/hmRiKgfS``)
is shown instead of :guilabel:`Create Short URL`, next to a clipboard
button – styled like the :guilabel:`Short URL` field in the redirect
record. Clicking the clipboard button copies the full URL (including the
site's base URL), and a notification confirms the copy.

..  figure:: /Images/ButtonCopied.png
    :class: with-border
    :alt: Page module showing a notification that the short URL has been
        copied to the clipboard

    Clicking the clipboard button copies the short URL and shows a
    confirmation notification

.. _editor-automatic:

Automatically generated short URLs
====================================

Depending on the site configuration, a short URL may already have been
created automatically when the page was first saved (see
:ref:`configuration`). In that case, the button on the page module will
already show the existing short URL – there is nothing further to do unless
you want to copy it.
