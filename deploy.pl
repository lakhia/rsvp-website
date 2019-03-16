#!/usr/bin/perl

use strict;
use File::Find;
use Cwd;

# Global variables
my $webpass = (shift or '');
my $mysqlpass = (shift or '');
my $dbname = (shift or '');

find (\&wanted, 'build');

sub wanted {
    return unless -f $_;

    if (m/oo_db.php/) {
        oo($_);
        return;
    } elsif (m/aux/) {
        aux($_);
        return;
    } elsif (m/index\.html/) {
        html($_);
        return;
    }
}

sub oo {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";
    while ($line = <IN>) {
        if ($line =~ m/dbhost =/) {
            $line =~ s/127.0.0.1/mysql-1.sfjamaat.org/;
        } elsif ($line =~ m/dbpassword =/) {
            $line =~ s/sffaiz-pass/$mysqlpass/;
        } elsif ($line =~ m/dbname =/ and $dbname) {
            $line =~ s/sffaiz/$dbname/;
        }
        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}

sub aux {
    return unless $webpass;
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";
    while ($line = <IN>) {
        if ($line =~ m/admin\@sfjamaat.org/) {
            $line =~ s/admin\@sfjamaat.org/$webpass/;
        }
        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}

sub html {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";
    while ($line = <IN>) {
        # Javascript methods
        $line =~ s/getClass/gC/g;
        $line =~ s/on(Count|RSVP|Rice|)Change/o$1/g;
        $line =~ s/getDisplayDate/gD/g;
        $line =~ s/changed/cg/g;
        $line =~ s/rsvpLabel/rL/g;
        $line =~ s/....Line//g;
        $line =~ s/raw/r/g;
        $line =~ s/next/n/g;
        # CSS names
        $line =~ s/sidebar/s/g;
        $line =~ s/nofoc/n/g;
        $line =~ s/noPrnt/p/g;
        $line =~ s/rsvpRow/r/g;
        $line =~ s/rsvpBtn/b/g;
        $line =~ s/;-o-[^;]+//g;
        $line =~ s/;-moz-[^;]+//g;
        $line =~ s/-webkit-[^;]+;//g;
        $line =~ s/;-ms-[^;]+//g;
        # Misc
        $line =~ s/(\w\w)\w+Controller/$1C/g;
        $line =~ s/\w\w\.html//g;
        $line =~ s/loading-bar/lb/g;
        $line =~ s/[Ll]oadingBar/lB/g;
        $line =~ s/&nbsp;/ /g;
        $line =~ s/menuBig/mB/g;
        $line =~ s/gone/gn/ig;
        $line =~ s/hideRow/hR/g;
        $line =~ s/greet/gr/g;
        $line =~ s/menuToggle/mT/g;
        $line =~ s/minWidth/m/g;
        $line =~ s/ }}/}}/g;
        $line =~ s/\{\{ /{{/g;
        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}
