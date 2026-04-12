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
    } elsif (m/AuthService/) {
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
        # CSS names
        $line =~ s/btn-secondary/bt-sc/g;
        $line =~ s/btn-primary/bt-pr/g;
        $line =~ s/btn-danger/bt-dg/g;
        $line =~ s/input-inline/i-in/g;
        $line =~ s/input-sm/i-sm/g;
        $line =~ s/select-filter/s-ft/g;
        $line =~ s/badge/bdg/g;
        $line =~ s/-brand-dark/-b-dk/g;
        $line =~ s/-yes-dark/-y-dk/g;
        $line =~ s/-no-dark/-n-dk/g;
        $line =~ s/font-medium/f-med/g;
        $line =~ s/transition-colors/t-col/g;
        $line =~ s/-gray-(\d)00/-g-$1/g;

        # Comments
        $line =~ s|/\*[^*]+\*/||;

        # Misc
        $line =~ s/menuBig/mB/g;
        $line =~ s|https://svelte.dev/e/|sdev|g;

        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}
