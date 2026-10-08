.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

.. _introduction-what:

What does it do?
=================

The extension adds a **Short URL** button to the button bar of the
:guilabel:`Web > Page` module. It lets editors create a short, memorable
redirect URL for the currently opened page without leaving the backend, and
without having to use the :guilabel:`System > Redirects` module directly.

A short URL is a regular TYPO3 redirect (:sql:`sys_redirect`) with redirect
type ``short_url``, pointing to the page via a ``t3://page?uid=…`` target.
The extension builds on top of :composer:`typo3/cms-redirects` and its
:php:`\TYPO3\CMS\Redirects\Service\ShortUrlService` to generate the actual,
unique short path.

.. _introduction-features:

Features
========

*  A toolbar button in the page module:

   -  If no short URL exists yet for the page, the button creates one.
   -  If a short URL already exists, the button instead displays it and
      copies the full URL to the clipboard when clicked.

*  Optional automatic generation of a short URL whenever a **new page** is
   created, restricted to configurable page types (:ref:`configuration`).

*  Both behaviours – the button and the automatic generation – can be
   configured per site.

.. _introduction-requirements:

Requirements
============

*  TYPO3 v14.3 or higher
*  PHP 8.3 or higher
*  :composer:`typo3/cms-redirects`
