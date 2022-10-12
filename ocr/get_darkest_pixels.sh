convert $(convert $1 +dither -colors 64 -depth 8 +repage -format %c histogram:info:- | sed -n 's/^.*: \(.*\) #.*$/\1/p' | tr -cs "0-9\n" " " | sed -n 's/^ *//p' | sed -n 's/ *$//p' | awk '{ i=NR; red[i]=$1; grn[i]=$2; blu[i]=$3; } END { for (i=1; i<=NR; i++) { lum[i]=int(0.29900*red[i]+0.58700*grn[i]+0.11400*blu[i]); print red[i], grn[i], blu[i], lum[i]; } } ' 2>/dev/null | sort -n -k 4,4 | awk '{ list=""; i=NR; red[i]=$1; grn[i]=$2; blu[i]=$3; } END { for (i=1; i<=NR; i++) { list=(list "\ " "xc:rgb("red[i]","grn[i]","blu[i]")"); }{ print list; } } ' 2>/dev/null) +append "miff:-" | convert - -depth 8 txt: | tail -n +2 | sed 's/^[ ]*//' | sed 's/[ ][ ]*/ /g' | cut -d\  -f3 | sort -d -u