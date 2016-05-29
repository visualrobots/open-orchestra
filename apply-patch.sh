#/bin/sh
VENDOR_OO_PATH="vendor/open-orchestra/"
PATCH_PATH="patch"

function apply_patch(){
	for folder in `ls $PATCH_PATH`; do
		for patch in `ls $PATCH_PATH/$folder`; do
			directory="$VENDOR_OO_PATH/$folder"
			patchFile="$PATCH_PATH/$folder/$patch"
			patch --directory=$directory -p1 < $patchFile
		done;
	done;
}

read -p "Do you want apply patch (y/n)? " answer
case ${answer:0:1} in
    y|Y )
        apply_patch
    ;;
    * )
        exit
    ;;
esac

