# Miiverse World
A Miiverse clone experience created by PF2M that allows you to create posts, communities and more with your friends and followers. Not finished, and probably never will be unless someone else wants to finish it.
## What is this?
It's a Miiverse clone, except you can make posts outside of communities and a feed system is implemented showing you the latest posts from people you follow and communities you've favorited. It also includes helpful pages like a user/community discovery helper, new features like tagging and private communities and the most complex search system of any Miiverse clone. However, it won't be fully finished by me, as I'm leaving all Miiverse clones and finding somewhere new to go. Therefore, I'm putting the source code up in case anyone wants to take up the torch and finish their own version. I put months into creating this before its cancellation, and would hate to see all of it go to waste.
## How do I install it?
To install Miiverse World, simply copy the root (the folder with the .htaccess) to a public-facing realm on an Apache server, import structure.sql to a MySQL server and add its authentication details to settings.php, editing it to your liking. Then you should be able to make an account, give it admin through any database management tool, and start using Miiverse World!
## To-do list
* Add community editing, with an interface for community banning and management
* Add audit logs, for both community admins and global admins
* Finish reporting; add it to comments, add a way to view them and add webhook support
* Finish blocking
* Add password resetting
* Add the ability to hide a user from recommendations
* Add post editing?
* Add messages?
## License
Miiverse World is licensed under the MIT license, meaning that anyone can use and edit it to their liking as long as the license file is retained. Some of the content in this repository is owned by Nintendo and Hatena; no copyright infringement is intended.