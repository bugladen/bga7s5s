magick '.\the docks.png' .\location-forum.png '.\grande bazaar.png' +append '.\city symbols.png'
magick '.\city symbols.png' '.\Crew Cap.png' .\Panache.png .\nail.png locker.png discard.png hand.png '.\oles inn.png' '.\gov gardens.png' +append step1.png
magick .\step1.png .\seals.png -append step2.png
magick .\step2.png CFI.png +append step3.png
magick seal.png initiative.png cost.png +append step4.png
magick step3.png step4.png -append boardResources.png
remove-item '.\city symbols.png'
remove-item '.\step1.png'
remove-item '.\step2.png'
remove-item '.\step3.png'
remove-item '.\step4.png'