#!/usr/bin/perl

use strict;
use File::Find;
use Cwd;

# Global variables
my $env_file = shift or die "Usage: $0 ENV-FILE \n\n(see .env.example)\n";
my %config = parse_env_file($env_file);

# uncomment for debugging config
#use Data::Dumper;
#print Dumper(\%config);

find (\&wanted, 'build');

sub wanted {
    return unless -f $_;

    if (m/oo_db.php/) {
        db_config($_);
    } elsif (m/aux/) {
        php_helper($_);
    } elsif (m/index\.html/) {
        html($_);
    }
}

sub parse_env_file {
    my $file = shift;
    my %env;

    open my $fh, '<', $file or die "Cannot open $file: $!";

    while (my $line = <$fh>) {
        # Remove leading/trailing whitespace
        $line =~ s/^\s+|\s+$//g;

        # Skip empty lines and comments
        next if $line eq '' || $line =~ /^#/;

        # Parse KEY=VALUE
        if ($line =~ /^([A-Z_]+)=(.*)$/) {
            my ($key, $value) = ($1, $2);

            # Remove quotes if present
            $value =~ s/^["']|["']$//g;

            $env{$key} = $value;
        }
    }

    close $fh;
    return %env;
}

sub db_config {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";
    while ($line = <IN>) {
        # Replace special values with config values
        if ($line =~ m/dbhost =/) {
            $line =~ s/localhost/$config{DB_HOST}/;
        } elsif ($line =~ m/dbpassword =/) {
            $line =~ s/sffaiz-pass/$config{DB_PASSWORD}/;
        } elsif ($line =~ m/dbusername =/) {
            $line =~ s/sffaiz/$config{DB_USERNAME}/;
        } elsif ($line =~ m/dbname =/) {
            $line =~ s/sffaiz/$config{DB_NAME}/;
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
        $line =~ s/admin\@sfjamaat.org/$config{EMAIL_ADMIN}/;
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
        $line =~ s/APP_NAME/$config{APP_NAME}/;
        $line =~ s/FEEDBACK_URL/$config{LINK_FEEDBACK}/;
        $line =~ s/PLANNING_URL/$config{LINK_PLANNING}/;
        $line =~ s/ADMIN_EMAIL/$config{EMAIL_ADMIN}/;
        $line =~ s/CONTACT_EMAIL/$config{EMAIL_CONTACT}/;
        $line =~ s/SECRETARY_EMAIL/$config{EMAIL_SECRETARY}/;
        $line =~ s/SECRETARY_TITLE/$config{SECRETARY_TITLE}/;

        # Javascript methods
        $line =~ s/getClass/gC/g;
        $line =~ s/on(..)[a-zA-Z]*Change/o$1/g;
        $line =~ s/onChange/oC/g;
        $line =~ s/onSizeChange/oSC/g;
        $line =~ s/onCheckboxClick/oCC/g;
        $line =~ s/onFilterChange/oFC/g;
        $line =~ s/getDisplayDate/gD/g;
        $line =~ s/generateLabels/gLB/g;
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
        $line =~ s/&nbsp;/ /g;
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
