#!/usr/bin/perl

use strict;
use File::Find;
use Cwd;
use YAML qw(LoadFile);
use Data::Dumper qw(Dumper);

# Global variables
my $config_file = shift or die "Usage: $0 YAML-FILE \n\n(see config/example.yaml)\n";
my $config = LoadFile($config_file);

# uncomment for debugging config
#print Dumper $config; 

find (\&wanted, 'build');

sub wanted {
    return unless -f $_;

    if (m/oo_db.php/) {
        db_config($_);
        return;
    } elsif (m/aux/) {
        php_helper($_);
        return;
    } elsif (m/index\.html/) {
        html($_);
        return;
    }
}

sub db_config {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";
    while ($line = <IN>) {
        if ($line =~ m/dbhost =/) {
            $line =~ s/127.0.0.1/$config->{'db'}->{'host'}/;
        } elsif ($line =~ m/dbpassword =/) {
            $line =~ s/sffaiz-pass/$config->{'db'}->{'password'}/;
        } elsif ($line =~ m/dbname =/) {
            $line =~ s/sffaiz/$config->{'db'}->{'name'}/;
        }
        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}

sub php_helper {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";
    while ($line = <IN>) {
        $line =~ s/admin\@sfjamaat.org/$config->{'email'}->{'admin'}/;
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
        # Template values
        $line =~ s/APP_NAME/$config->{'app'}->{'name'}/;
        $line =~ s/FEEDBACK_URL/$config->{'links'}->{'feedback'}/;
        $line =~ s/PLANNING_URL/$config->{'links'}->{'planning'}/;
        $line =~ s/ADMIN_EMAIL/$config->{'email'}->{'admin'}/;
        $line =~ s/CONTACT_EMAIL/$config->{'email'}->{'contact'}/;
        $line =~ s/SECRETARY_EMAIL/$config->{'email'}->{'secretary'}/;

        # Javascript methods
        $line =~ s/getClass/gC/g;
        $line =~ s/on(..)[a-zA-Z]*Change/o$1/g;
        $line =~ s/onChange/oC/g;
        $line =~ s/onSizeChange/oSC/g;
        $line =~ s/onCheckboxClick/oCC/g;
        $line =~ s/onFilterChange/oFC/g;
        $line =~ s/getDisplayDate/gD/g;
        $line =~ s/getRawDate/gR/g;
        $line =~ s/getSizes/gSz/g;
        $line =~ s/changed/cg/g;
        $line =~ s/rsvpLabel/rL/g;
        $line =~ s/....Line//g;
        $line =~ s/raw/r/g;
        $line =~ s/next/n/g;
        $line =~ s/submit/sb/g;
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
        $line =~ s/menuToggle/mT/g;
        $line =~ s/minWidth/m/g;
        $line =~ s/filterNames/fN/g;
        $line =~ s/filterFunc/fF/g;
        $line =~ s/sortColumn/sC/g;
        $line =~ s/sorterFunc/sF/g;
        $line =~ s/ }}/}}/g;
        $line =~ s/\{\{ /{{/g;
        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}
