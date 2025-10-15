function beprint(myframe) {
    //alert(myframe);
    // Main_Frame ist der Name des Quelltext-Frames
    parent.frames.list_frame.focus();
    // Diese Zeile ist fuer den Internet Explorer, da er
    // normalerweise trotz richtiger Angabe nur das fokusierte
    // Frame-Fenster druckt.
    parent.frames.list_frame.print();
}