# LWS
Lion Web Server
Version 1.0

# Requirements
1. PHP and PHP-CGI
2. PHP Json Extension
3. PHP PCRE Extension (Perl-Compatible Regex)<br>
<b>Minimum PHP binaries version: 5.6 (with 5.4 haven't tested yet, may also work)</b>

# Version Info
Version 1.0

Supported protocols: HTTP/1.0, HTTP/1.1<br>
Supported OS: Windows (7 or higher), Linux (Debian, Ubuntu)<br>
*The list of the supported Operating Systems is made based on tests only.

# Installing
1. Download content from the git repository ( use either Zip-download option or clone it via   `git clone https://github.com/leo-mail/LWS.git` )
2. Rename Start file to Start.sh if you're running under Unix-like system, if not - Start.bat for MS Windows, then launch it<br>
Then it will automatically identify php packages if it's already installed, if not - script will install it for you

Notice: Superuser privelegies required when using Linux (sudo)

Notice-2: under Windows it is possible to put php binaries in /php or /bin sub-folders

# Troubleshooting
1. The port is already taken - check for other servers running on current machine, for example port 80 is default blocked by Apache on Debian
2. Cannot start because port opening is prohibited - check & update your system's security settings, its security protocols maybe blocking some applications from opening TCP server ( socket connections )
3. Bugs - report any bugs to out email address or to the <a href="https://github.com/leo-mail/LWS/issues">Issues</a> section'

# Configuring
Open config.xml and edit values on your own (Further instruction will be published after upgrading to version 1.1)

# About
Simple HTTP server, written using system functions (console scripts) and pure php code<br>
Uses CGI and PHP Streams to provide responses to the requests.
