# LWS
Lion Web Server
Version 1.0

# Requirements
1. PHP and PHP-CGI
2. PHP Json Extension
3. PHP PCRE Extension (Perl-Compatible Regex)
 
# Version Info
Version 1.0

Supported protocols: HTTP/1.0, HTTP/1.1<br>
Supported OS: Windows (7 or higher), Linux (Debian, Ubuntu)<br>
*The list of the supported Operating Systems is made based on tests only.

# Installing

1. Download content from the git repository ( use either Zip-download option or clone it via   `git clone https://github.com/leo-mail/LWS.git` )
2. Launch Start.sh if you're running under Linux-like system, if not - Start.bat for MS Windows<br>
Then it will automatically identify php packages if it's already installed, if not - script will install it for you

Notice: Superuser privelegies required when using Linux (sudo)

# Troubleshooting
1. The port is already taken - check for other servers running on current machine, for example port 80 is default blocked by Apache on Debian
2. Cannot start because port opening is prohibited - check & update your system's security settings, its security protocols maybe blocking some applications from opening TCP server ( socket connections )
3. Bugs - report any bugs to out email address or to the <a href="/issues">Issues</a> section'

# Configuring
Open config.xml and edit values on your own (Further instruction will be published after upgrading to version 1.1)

# About
Simple HTTP server, written using system functions (console scripts) and pure php code
Uses CGI and PHP Streams to provide responses to the requests.
