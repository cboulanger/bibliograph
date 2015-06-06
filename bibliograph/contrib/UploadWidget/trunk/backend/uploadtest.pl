#!/usr/local/bin/perl -wT
#
#/* ************************************************************************
#
#   qooxdoo - the new era of web development
#
#   http://qooxdoo.org
#
#   Copyright:
#     2007 Visionet GmbH, http://www.visionet.de
#
#   License:
#     LGPL: http://www.gnu.org/licenses/lgpl.html
#     EPL: http://www.eclipse.org/org/documents/epl-v10.php
#     See the LICENSE file in the project's top-level directory for details.
#
#   Authors:
#     * Dietrich Streifert (level420)
#
#************************************************************************ */
##
delete $ENV{ 'PATH' };
$ENV{PATH} = "/usr/bin";

use strict;

package UploadTest;

use base 'CGI::Application';
use CGI;
use CGI::Carp qw(fatalsToBrowser);

sub setup {
    my $self = shift;
    $self->start_mode('upload');
    $self->run_modes(
		     'upload'						 => 'upload_pl',
		     'upload_multiple'				 => 'upload_multiple_pl',
		     );
}

sub upload_pl {

print STDERR "upload_pl\n";

    my $self = shift;
    my $q = $self->query();

	my $uploadfile = $q->param('uploadfile');

print STDERR "uploadfile: $uploadfile\n";

print STDERR "request_method: " . $q->request_method() . "\n";
print STDERR "content_type" . $q->content_type() . "\n";

	my $buffer;
	my $length = 0;
	my $bytesread;
	my $data = "";

	while ($bytesread = read($uploadfile,$buffer,1024) and
		   $length < 1024*1024 ) {
		$length += $bytesread;
		$data .= $buffer;
	}

print STDERR "length: |$length|\n";

	my $output = ''; # <?xml version="1.0" encoding="ISO-8859-1"?>';
	$output .= '<xmlhttp>';
	$output .= '<response><length>' . $length . '</length>' .
			   '</response>';
	$output .= '</xmlhttp>';
	$self->header_props( -type => 'text/plain; name=response.xml', # 'text/plain' ,
						 -Content_Disposition => 'inline; filename=response.xml',
						 -Content_Length => length($output) );
	return $output;	return $output;

}


sub upload_multiple_pl {

print STDERR "upload_multiple_pl\n";

    my $self = shift;
    my $q = $self->query();

	my $uploadfile1 = $q->param('uploadfile1');
	my $uploadfile2 = $q->param('uploadfile2');
	my $uploadfile3 = $q->param('uploadfile3');

print STDERR "uploadfile1: $uploadfile1\n";
print STDERR "uploadfile2: $uploadfile2\n";
print STDERR "uploadfile3: $uploadfile3\n";

print STDERR "request_method: " . $q->request_method() . "\n";
print STDERR "content_type" . $q->content_type() . "\n";

	my $buffer;
	my $length1 = 0;
	my $length2 = 0;
	my $length3 = 0;
	my $bytesread;
	my $data = "";

	if(defined($uploadfile1) and $uploadfile1 ne '') {
		while ($bytesread = read($uploadfile1,$buffer,1024) and
			   $length1 < 1024*1024 ) {
			$length1 += $bytesread;
			$data .= $buffer;
		}
	}

	if(defined($uploadfile2) and $uploadfile2 ne '') {
		while ($bytesread = read($uploadfile2,$buffer,1024) and
			   $length2 < 1024*1024 ) {
			$length2 += $bytesread;
			$data .= $buffer;
		}
	}

	if(defined($uploadfile3) and $uploadfile3 ne '') {
		while ($bytesread = read($uploadfile3,$buffer,1024) and
			   $length3 < 1024*1024 ) {
			$length3 += $bytesread;
			$data .= $buffer;
		}
	}

print STDERR "length1: |$length1|\n";
print STDERR "length2: |$length2|\n";
print STDERR "length3: |$length3|\n";

	my $output = ''; # <?xml version="1.0" encoding="ISO-8859-1"?>';
	$output .= '<xmlhttp>';
	$output .= '<response>' . 
			   '<length1>' . $length1 . '</length1>' .
			   '<length2>' . $length2 . '</length2>' .
			   '<length3>' . $length3 . '</length3>' .
			   '</response>';
	$output .= '</xmlhttp>';
	$self->header_props( -type => 'text/plain; name=response.xml', # 'text/plain' ,
						 -Content_Disposition => 'inline; filename=response.xml',
						 -Content_Length => length($output) );
	return $output;	return $output;

}

1;

my $app = UploadTest->new();
$app->run();
