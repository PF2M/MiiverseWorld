<?php
// Define your settings here. Set them as null or empty to not use their respective features.

// General settings.
const CONTACT_EMAIL = 'miiverseworld@reconmail.com'; // The email you want to be contacted at. Optional.
const HTTPS_PROXY = false; // Set this to true if you're using an HTTPS proxy like Cloudflare for your site's main domain, or false if you're not or don't know what that is. Required.
const TIMEZONE = 'America/New_York'; // The timezone used by the site. Required.

// Database settings.
const DB_HOST = 'localhost'; // The hostname of your database server. Required.
const DB_USER = 'root'; // The username you'll use to access the database. Required.
const DB_PASS = ''; // The password you'll use to access the database. Optional.
const DB_NAME = 'miiverse_world'; // The name of the database. Required.

// Cloudinary settings.
const CLOUDINARY_CLOUDNAME = 'reverb'; // The cloud name of your Cloudinary account. Required.
const CLOUDINARY_UPLOADPRESET = 'reverb-mobile'; // The unsigned upload preset of your Cloudinary account. Required.

// ReCAPTCHA settings.
const RECAPTCHA_PUBLIC = null; // Your ReCAPTCHA public key. Optional.
const RECAPTCHA_SECRET = null; // Your ReCAPTCHA private key. Optional.
?>