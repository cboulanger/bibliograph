#!/bin/sh

echo "Installing citeproc-hs with pandoc";
echo "==================================";

echo " * Downloading binary (0.1) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/binary/0.5.0.1/binary-0.5.0.1.tar.gz
tar xzf binary-0.5.0.1.tar.gz

echo " * Downloading tagsoup (0.6) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/tagsoup/0.6/tagsoup-0.6.tar.gz 
tar xzf tagsoup-0.6.tar.gz 

echo " * Downloading utf8-string (0.3.4) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/utf8-string/0.3.4/utf8-string-0.3.4.tar.gz
tar xzf utf8-string-0.3.4.tar.gz

echo " * Downloading zip-archive (0.1.1.3) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/zip-archive/0.1.1.3/zip-archive-0.1.1.3.tar.gz 
tar xzf zip-archive-0.1.1.3.tar.gz

echo " * Downloading zlib (0.5.0.0) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/zlib/0.5.0.0/zlib-0.5.0.0.tar.gz 
tar xzf zlib-0.5.0.0.tar.gz 

echo " * Downloading digest (0.0.0.5) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/digest/0.0.0.5/digest-0.0.0.5.tar.gz 
tar xzf digest-0.0.0.5.tar.gz 

echo " * Downloading curl (1.3.4) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/curl/1.3.4/curl-1.3.4.tar.gz
tar xzf curl-1.3.4.tar.gz

echo " * Downloading hxt (8.3.0) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/hxt/8.3.0/hxt-8.3.0.tar.gz
tar xzf hxt-8.3.0.tar.gz

echo " * Downloading pandoc (1.2) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/pandoc/1.2/pandoc-1.2.tar.gz 
tar xzf pandoc-1.2.tar.gz 

echo " * Downloading citeproc-hs (0.2) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/citeproc-hs/0.2/citeproc-hs-0.2.tar.gz 
tar xzf citeproc-hs-0.2.tar.gz 

echo " * Downloading bibutils (4.1, patched)...";
wget -N --quiet http://code.haskell.org/hs-bibutils/bibutils-4.1-ar.tar.gz 
tar xzf bibutils-4.1-ar.tar.gz 

echo " * Downloading hs-bibutils (0.1) ...";
wget -N --quiet http://hackage.haskell.org/packages/archive/hs-bibutils/0.1/hs-bibutils-0.1.tar.gz 
tar xzf hs-bibutils-0.1.tar.gz 

echo
echo "Compiling, this will take a while ...";
echo

echo " * Compiling binary (0.1) ...";
cd binary-0.5.0.1
runhaskell Setup.lhs configure
runhaskell Setup.lhs build
sudo runhaskell Setup.lhs install

echo " * Compiling tagsoup (0.6) ...";
cd ../tagsoup-0.6 
runhaskell Setup.hs configure
runhaskell Setup.hs build
sudo runhaskell Setup.hs install

echo " * Compiling utf8-string (0.3.4) ...";
cd ../utf8-string-0.3.4
runhaskell Setup.lhs configure
runhaskell Setup.lhs build
sudo runhaskell Setup.lhs install

echo " * Compiling zlib (0.5.0.0) ...";
cd ../zlib-0.5.0.0
runhaskell Setup.hs configure
runhaskell Setup.hs build
sudo runhaskell Setup.hs install

echo " * Compiling zip-archive (0.1.1.3) ...";
cd ../zip-archive-0.1.1.3
runhaskell Setup.lhs configure
runhaskell Setup.lhs build
sudo runhaskell Setup.lhs install

echo " * Compiling digest (0.0.0.5) ...";
cd ../digest-0.0.0.5
runhaskell Setup.hs configure
runhaskell Setup.hs build
sudo runhaskell Setup.hs install 

echo " * Compiling curl (1.3.4) ...";
cd ../curl-1.3.4
runhaskell Setup.hs configure
runhaskell Setup.hs build
sudo runhaskell Setup.hs install 

echo " * Compiling hxt (8.3.0) ...";
cd ../hxt-8.3.0
runhaskell Setup.lhs configure
runhaskell Setup.lhs build
sudo runhaskell Setup.lhs install

echo " * Compiling bibutils (4.1, patched)...";
cd ../bibutils-4.1-ar
./configure
make
# make installlib does not work on the mac, need to do 
sudo /usr/bin/ginstall -c lib/libbibutils.dylib /usr/local/lib 

echo " * Compiling hs-bibutils (0.1) ...";
cd ../hs-bibutils-0.1
runhaskell Setup.lhs configure --extra-include-dirs=../bibutils-4.1-ar/lib/
runhaskell Setup.lhs build
sudo runhaskell Setup.lhs install

echo " * Compiling citeproc-hs (0.2) ...";
cd ../citeproc-hs-0.2
runhaskell Setup.lhs configure -fbibutils
runhaskell Setup.lhs build
sudo runhaskell Setup.lhs install

echo " * Compiling pandoc (1.2) ...";
cd ../pandoc-1.2
runhaskell Setup.hs configure -f citeproc
runhaskell Setup.hs build
sudo runhaskell Setup.hs install 

echo
echo "Installation finished."

