#!/usr/bin/perl

use strict;
use File::Find;
use Cwd;

# Global variables
my $env_file = shift or die "Usage: $0 ENV-FILE \n\n(see .env.example)\n";
my %config = parse_env_file($env_file);
my $root_dir = getcwd();  # Store root directory for template paths

# uncomment for debugging config
#use Data::Dumper;
#print Dumper(\%config);

find (\&wanted, 'build');

sub wanted {
    return unless -f $_;

    if (m/oo_db.php/) {
        db_config($_);
        return;
    } elsif (m/config\.php/) {
        config($_);
        return;
    } elsif (m/aux/) {
        php_helper($_);
        return;
    } elsif (m/rsvp\.php/) {
        rsvp_config($_);
        return;
    } elsif (m/index\.html/) {
        html($_);
        return;
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
        if ($line =~ m/dbhost =/) {
            $line =~ s/127.0.0.1/$config{'DB_HOST'}/;
            $line =~ s/'db'/'$config{'DB_HOST'}'/;
        } elsif ($line =~ m/dbpassword =/) {
            $line =~ s/sffaiz-pass/$config{'DB_PASSWORD'}/;
        } elsif ($line =~ m/dbusername =/) {
            $line =~ s/sffaiz/$config{'DB_USERNAME'}/;
        } elsif ($line =~ m/dbname =/) {
            $line =~ s/sffaiz/$config{'DB_NAME'}/;
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
        # Existing email replacement
        $line =~ s/admin\@sfjamaat.org/$config{'EMAIL_ADMIN'}/;

        # Template variable replacement for cutoff mode
        if ($line =~ /\{\{CUTOFF_TIME_IMPLEMENTATION\}\}/) {
            my $template_file = $config{'RSVP_CUTOFF_MODE'} eq 'weekly'
                ? "$root_dir/deploy-templates/cutoff_weekly.php"
                : "$root_dir/deploy-templates/cutoff_daily.php";

            open TEMPLATE, $template_file or die "Cannot open template $template_file: $!\nMake sure RSVP_CUTOFF_MODE is set correctly in .env";

            # Inject template with auto-generated comment
            print OUT "        // Auto-generated: $config{'RSVP_CUTOFF_MODE'} mode (from .env)\n";
            while (my $tpl_line = <TEMPLATE>) {
                print OUT "        " . $tpl_line;
            }
            close TEMPLATE;
            next;
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
        # Template values
        $line =~ s/APP_NAME/$config{'APP_NAME'}/;
        $line =~ s/FEEDBACK_URL/$config{'LINK_FEEDBACK'}/;
        $line =~ s/PLANNING_URL/$config{'LINK_PLANNING'}/;
        $line =~ s/ADMIN_EMAIL/$config{'EMAIL_ADMIN'}/;
        $line =~ s/CONTACT_EMAIL/$config{'EMAIL_CONTACT'}/;
        $line =~ s/SECRETARY_EMAIL/$config{'EMAIL_SECRETARY'}/;
        $line =~ s/SECRETARY_TITLE/$config{'SECRETARY_TITLE'}/;

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

sub rsvp_config {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";

    while ($line = <IN>) {
        # Template variable replacement for size selection
        if ($line =~ /\{\{SIZE_SELECTION_IMPLEMENTATION\}\}/) {
            my $mode = $config{'SIZE_SELECTION_MODE'};
            my $template_file;

            if ($mode eq 'any') {
                $template_file = "$root_dir/deploy-templates/size_any.php";
            } elsif ($mode eq 'downgrade-only') {
                $template_file = "$root_dir/deploy-templates/size_downgrade.php";
            } elsif ($mode eq 'plus-minus-one') {
                $template_file = "$root_dir/deploy-templates/size_plus_minus_one.php";
            } else {
                die "Unknown SIZE_SELECTION_MODE: $mode\nValid values: any, downgrade-only, plus-minus-one";
            }

            open TEMPLATE, $template_file or die "Cannot open template $template_file: $!\nMake sure SIZE_SELECTION_MODE is set correctly in .env";

            # Inject template with auto-generated comment
            print OUT "    // Auto-generated: $mode mode (from .env)\n";
            while (my $tpl_line = <TEMPLATE>) {
                print OUT "    " . $tpl_line;
            }
            close TEMPLATE;
            next;
        }

        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}

sub config {
    my $line;
    $_ = shift;
    open IN, $_ or die "Cannot open $!";
    open OUT, ">$_.backup" or die "Cannot open $!";

    my $cutoff_mode = $config{'RSVP_CUTOFF_MODE'};

    while ($line = <IN>) {
        # Skip weekly constants if in daily mode
        if ($cutoff_mode eq 'daily' && $line =~ /const WEEKLY_/) {
            print OUT "    // WEEKLY_* constants removed (daily mode active)\n" if $line =~ /WEEKLY_CUTOFF_DAY/;
            next;
        }

        # Skip daily constants if in weekly mode
        if ($cutoff_mode eq 'weekly' && $line =~ /const DAILY_/) {
            print OUT "    // DAILY_* constants removed (weekly mode active)\n" if $line =~ /DAILY_CUTOFF_TIME/;
            next;
        }

        # Cutoff configuration
        if ($line =~ m/CUTOFF_MODE =/) {
            $line =~ s/"daily"/"$config{'RSVP_CUTOFF_MODE'}"/;
        } elsif ($line =~ m/TIMEZONE =/) {
            $line =~ s/"America\/Los_Angeles"/"$config{'RSVP_TIMEZONE'}"/;
        } elsif ($line =~ m/WEEKLY_CUTOFF_DAY =/) {
            $line =~ s/"Thursday"/"$config{'RSVP_WEEKLY_CUTOFF_DAY'}"/;
        } elsif ($line =~ m/WEEKLY_CUTOFF_TIME =/) {
            $line =~ s/"23:00"/"$config{'RSVP_WEEKLY_CUTOFF_TIME'}"/;
        } elsif ($line =~ m/WEEKLY_WEEK_START =/) {
            $line =~ s/"Monday"/"$config{'RSVP_WEEKLY_WEEK_START'}"/;
        } elsif ($line =~ m/DAILY_CUTOFF_TIME =/) {
            $line =~ s/"21:00"/"$config{'RSVP_DAILY_CUTOFF_TIME'}"/;
        } elsif ($line =~ m/DAILY_ADVANCE_DAYS =/) {
            $line =~ s/1/$config{'RSVP_DAILY_ADVANCE_DAYS'}/;
        } elsif ($line =~ m/const THAALI_SIZES = /) {
            # Convert THAALI_SIZES from string to array
            my @sizes = split(',', $config{'THAALI_SIZES'});
            @sizes = map { s/^\s+|\s+$//gr } @sizes;  # Trim whitespace
            my $sizes_array = '["' . join('", "', @sizes) . '"]';
            $line =~ s/ = "[^"]+";/ = $sizes_array;/;
        }
        print OUT $line;
    }
    close OUT;
    close IN;
    rename "$_.backup", $_;
}
