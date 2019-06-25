[fork](https://bitbucket.org/snitchashor/php-ext-authevesso/src/master/)

# phpBB 3.2 Auth Evesso by Snitch Ashor

**This project is work in progrees! Use it at your own risk.**

phpBB 3.2 Authentication Provider for the EVE Online SSO (using ESI)

## Changelog:

1.0.0a

- initial commit

1.0.1a 

- cron handling 
- session management

1.0.2a (broken)

- language handling

1.0.3a

- fixed adm not working due to language setup

1.0.4a

- increased compatibility
- added corp / alliance display to users

1.0.5a

- fixed forum group permissions being not updated due to caching

1.0.6a

- fixed permission bleeding when using the cron job

1.1b

- fixed non alphanumericals for corp/alliance names (API returning multiples)

1.2b

- selectable scopes
- option to check refresh tokens during the cron run
- option to allow only members of certain corps/alliances
- chenged login button to the official login with eve graphic
- cron job now logs its actions
- cleanup
- bugfixes

1.2.1b

- bugfixes

1.2.2b

- fixed corp and alliance names not showing up

1.2.3b

- fixed esi base URL

1.2.4b

- changed method to select virtual TS server instance

## Current features:

- phpBB auth provider (replaces regular login completely)
- Login / Registration using EVE accounts
- Group management based on corp / alliance (using ESI to fetch)
- Teamspeak serverGroup management based on corp / alliance 


## Requirements:

- phpBB 3.2 or above
- php5.6 or above with php-curl installed
- A valid EVE Online subscription (you need one to register a developer app.)

## Installation and setup:

**This extension is under development, if you install it on anything but a fresh and empty board, backup your database and files now.**

1. Upload the contents of the zip to your forum root (the zip should already contain the directory structure /ext/snitch/authevesso)
2. Go to the ACP
3. Enable the extension under 'Customise', if it doesnt show up check the directory structure.
4. Go to developers.eveonline.com and create an App, select API access, the scope esi-corporations.read_corporation_membership.v1 and set the callback url to <server>/<forumurl>/app.php/authevesso/login
5. Go to you forum ACP: General - Client communication - Authentication
6. Enter your app id and secret you got in the above step, as well as the Admin character name (Important: This has to be an eve character you will use from now on to log in as admin. If this char already exists as a board user, make him a founder and grant all permissions now.)
7. Save Settings
8. Change authentication method to Evesso and save.
9. Log in with the admin EVE char and purge the forum cache in the ACP.
10. Start adding Groups / Teamspeak groups under 'Extensions' 

if something goes wrong and you locked yourself out, access your database and find the key auth_method in your phpbb_config table and change it from 'evesso' to 'db'.


## To do:


- Implement logging
- Testing
- Better session management (when logging in from multiple devices)
- ??? 


## A few notes how its working:

This extension automatically creates a forum user account for evey user that logs in with his eve account.  
It only handles the groups configured in the extensions ACP menu, it will not add OR REMOVE from any of the groups / teamspeak groups not entered there.  
It will not touch the permissions / groups of Administrators (user level Administrator, not just in the admin group)  
phpBB3.1+ has a new cron system. if you wish to run the cron jobs manually, the command to do so is: php <install_dir>/bin/phpbbcli.php cron:run (set to run e.g. once in 15 minutes, dont worry, the actual jobs run at different intervals) In that case, go to your boards server settings and set 'run periodic tasks from system...' to 'yes'.  
Cron jobs will run for a configurable duration, and fetch as many users as possible (and set Teamspek permission). If it did not check all users it will run again on the next execution (e.g. after 15 minutes) and only after it handled all the users it will wait a configurable time (e.g. 12 hours) for the next execution. This might be needed for boards with lots of users.  
Currently the script will not deactivate the users. It it fails to fetch the corp for a character, this means something went wrong when contacting the api and it will not alter that users permissions / groups.  


## Credits:

Original phpbb 3.0 [EVE mod]: https://forums.eveonline.com/default.aspx?g=posts&m=184934 by Cyerus  
Inspired by phpBB 3.1 [Authentication Provider for Shibboleth]: https://github.com/ck-ws/phpbb-ext-auth-shibboleth  
Using the [Teamspeak PHP framework]: https://docs.planetteamspeak.com/ts3/php/framework/  
ESI client generated using [swagger codegen]: https://github.com/swagger-api/swagger-codegen


## Translations:

- English
- Russian
  

## Speacial thanks:

Jintaro Keo for a lot of testing and the russian translation


Happy testing,  

Report any bugs you ancounter here or to admin@brgf.de.
If you wanna support this project, feel free to throw some ISK at Snitch Ashor  

o7, Snitch

