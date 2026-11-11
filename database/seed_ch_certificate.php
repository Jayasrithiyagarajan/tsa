<?php
// F-7.5.1-CL 029 Crane Health Check as a certificate template (layout 'ch'): cover fields + one Yes / No / N/A select per check row.
// 'section' / 'item' keep the form text used by documents/certificate_ch.php (N/A prints in the REMARKS column, as on the form).
$items = array (
  0 => 
  array (
    'key' => 'i001',
    'label' => 'Vessel Class Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Vessel Class Certificate',
  ),
  1 => 
  array (
    'key' => 'i002',
    'label' => 'Crane Class Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Crane Class Certificate',
  ),
  2 => 
  array (
    'key' => 'i003',
    'label' => 'Crane Class Load Test Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Crane Class Load Test Certificate',
  ),
  3 => 
  array (
    'key' => 'i004',
    'label' => 'LMI Calibration Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'LMI Calibration Certificate',
  ),
  4 => 
  array (
    'key' => 'i005',
    'label' => 'Manufacturer Manual',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Manufacturer Manual',
  ),
  5 => 
  array (
    'key' => 'i006',
    'label' => 'Operator Logbook',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Operator Logbook',
  ),
  6 => 
  array (
    'key' => 'i007',
    'label' => 'Daily Checklist',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Daily Checklist',
  ),
  7 => 
  array (
    'key' => 'i008',
    'label' => 'Operator Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Operator Certificate',
  ),
  8 => 
  array (
    'key' => 'i009',
    'label' => 'Operator Medical Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Operator Medical Report',
  ),
  9 => 
  array (
    'key' => 'i010',
    'label' => 'Crane Inspection Report (Blue Sticker)',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Crane Inspection Report (Blue Sticker)',
  ),
  10 => 
  array (
    'key' => 'i011',
    'label' => 'Main Hoist Wire Rope Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Main Hoist Wire Rope Certificate',
  ),
  11 => 
  array (
    'key' => 'i012',
    'label' => 'Aux Hoist Wire Rope Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Aux Hoist Wire Rope Certificate',
  ),
  12 => 
  array (
    'key' => 'i013',
    'label' => 'Boom Hoist Wire Rope Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Boom Hoist Wire Rope Certificate',
  ),
  13 => 
  array (
    'key' => 'i014',
    'label' => 'Main Hook Block Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Main Hook Block Certificate',
  ),
  14 => 
  array (
    'key' => 'i015',
    'label' => 'Aux Hook Block Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Aux Hook Block Certificate',
  ),
  15 => 
  array (
    'key' => 'i016',
    'label' => 'Wedge Socket / End Termination Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Wedge Socket / End Termination Certificate',
  ),
  16 => 
  array (
    'key' => 'i017',
    'label' => 'Slew Bearing Test Report (Rocking Test)',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Slew Bearing Test Report (Rocking Test)',
  ),
  17 => 
  array (
    'key' => 'i018',
    'label' => 'Slew Gear Report / Pinion Backlash Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Slew Gear Report / Pinion Backlash Report',
  ),
  18 => 
  array (
    'key' => 'i019',
    'label' => 'Crane Structure NDT Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Crane Structure NDT Report',
  ),
  19 => 
  array (
    'key' => 'i020',
    'label' => 'Main and Aux Bock Hooks NDT Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Main and Aux Bock Hooks NDT Report',
  ),
  20 => 
  array (
    'key' => 'i021',
    'label' => 'Cabin Fire Extinguisher Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Cabin Fire Extinguisher Certificate',
  ),
  21 => 
  array (
    'key' => 'i022',
    'label' => 'Cabin Breathing Apparatus Certificate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Cabin Breathing Apparatus Certificate',
  ),
  22 => 
  array (
    'key' => 'i023',
    'label' => 'Grease Sample Analysis Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Grease Sample Analysis Report',
  ),
  23 => 
  array (
    'key' => 'i024',
    'label' => 'Hydraulic Oil Analysis Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Hydraulic Oil Analysis Report',
  ),
  24 => 
  array (
    'key' => 'i025',
    'label' => 'Other Lube Oil Analysis Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Other Lube Oil Analysis Report',
  ),
  25 => 
  array (
    'key' => 'i026',
    'label' => 'Others',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Document for Review:',
    'item' => 'Others',
  ),
  26 => 
  array (
    'key' => 'i027',
    'label' => 'Daily Check List',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Crane Maintenance Records:',
    'item' => 'Daily Check List',
  ),
  27 => 
  array (
    'key' => 'i028',
    'label' => 'Routine PM Report (Quarterly / Biannual / Annual)',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Crane Maintenance Records:',
    'item' => 'Routine PM Report (Quarterly / Biannual / Annual)',
  ),
  28 => 
  array (
    'key' => 'i029',
    'label' => 'Motor Maintenance Report',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Crane Maintenance Records:',
    'item' => 'Motor Maintenance Report',
  ),
  29 => 
  array (
    'key' => 'i030',
    'label' => 'Others',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'Crane Maintenance Records:',
    'item' => 'Others',
  ),
  30 => 
  array (
    'key' => 'i031',
    'label' => 'A sign is posted warning the operator not to rely solel…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'A sign is posted warning the operator not to rely solely on any automatic device as a substitute for safe operating practice',
  ),
  31 => 
  array (
    'key' => 'i032',
    'label' => 'A durable rating chart with legible letters and figures…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'A durable rating chart with legible letters and figures is provided and fixed at a location visible to the operator while seated at his control station',
  ),
  32 => 
  array (
    'key' => 'i033',
    'label' => 'Load ratings at all operating radii and boom angles and…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Load ratings at all operating radii and boom angles and for all permissible boom lengths, jib lengths and angles are available',
  ),
  33 => 
  array (
    'key' => 'i034',
    'label' => 'Recommended parts of hoist reeving, size and type of ro…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Recommended parts of hoist reeving, size and type of rope for various loads are shown either on the rating chart or in the operating manual',
  ),
  34 => 
  array (
    'key' => 'i035',
    'label' => 'Essential precautionary or warning notes relative to li…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Essential precautionary or warning notes relative to limitations on equipment, operating procedure and stability factors are provided on threating chart or the operating manual',
  ),
  35 => 
  array (
    'key' => 'i036',
    'label' => 'Structure integrity and capability and verified to with…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Structure integrity and capability and verified to withstand the proposed or actual duty',
  ),
  36 => 
  array (
    'key' => 'i037',
    'label' => 'Protective guards are in place and secured in areas of …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Protective guards are in place and secured in areas of potential hazard',
  ),
  37 => 
  array (
    'key' => 'i038',
    'label' => 'Navigational lights are provided',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Navigational lights are provided',
  ),
  38 => 
  array (
    'key' => 'i039',
    'label' => 'Flame arrestor on fill and vent lines of gasoline tank …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Flame arrestor on fill and vent lines of gasoline tank is provided',
  ),
  39 => 
  array (
    'key' => 'i040',
    'label' => 'Fuel tank is equipped with self-closing filler cap',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Fuel tank is equipped with self-closing filler cap',
  ),
  40 => 
  array (
    'key' => 'i041',
    'label' => 'Vertical clearance between crane lowest point and floor…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Vertical clearance between crane lowest point and floor level of boat or barge is at least 7 feet (if less than 7 feet, barricading is to be provided to protect personnel from rotating sections)',
  ),
  41 => 
  array (
    'key' => 'i042',
    'label' => 'All controls are within the reach of the operator and c…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'All controls are within the reach of the operator and clearly marked with functions and modes of operation',
  ),
  42 => 
  array (
    'key' => 'i043',
    'label' => 'The remote control of the crane is properly working, an…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'The remote control of the crane is properly working, and the crane stops if control signal becomes ineffective (if fitted)',
  ),
  43 => 
  array (
    'key' => 'i044',
    'label' => 'Electrical motors disconnect from power failure',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Electrical motors disconnect from power failure',
  ),
  44 => 
  array (
    'key' => 'i045',
    'label' => 'Electrical motors do not start on power return',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Electrical motors do not start on power return',
  ),
  45 => 
  array (
    'key' => 'i046',
    'label' => 'Over-speeding prevention device is provided from electr…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Over-speeding prevention device is provided from electric motor operated cranes',
  ),
  46 => 
  array (
    'key' => 'i047',
    'label' => 'Controls forces are acceptable and within the capabilit…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Controls forces are acceptable and within the capabilities of operator
* Power plant controls are provided to
* Control start, stop and lock in off position
* Control engine speed,
* Stop diesel engines in emergencies, Shift selective transmissions',
  ),
  47 => 
  array (
    'key' => 'i048',
    'label' => 'Controls travel distances are:',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Controls travel distances are:
* Hand levers not greater than 14" (356mm) from neutral position on two way and not greater than 24" (610mm) on one-way levers Foot pedals not greater than 10" (254mm)',
  ),
  48 => 
  array (
    'key' => 'i049',
    'label' => 'Clutch is operating properly and within the reach of op…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Clutch is operating properly and within the reach of operator station',
  ),
  49 => 
  array (
    'key' => 'i050',
    'label' => 'Electrical control panels are fixed, protected and free…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Electrical control panels are fixed, protected and free of any damages',
  ),
  50 => 
  array (
    'key' => 'i051',
    'label' => 'Resistors and connections are corrosion free, protected…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Resistors and connections are corrosion free, protected, ventilated and installed to prevent the accumulation of combustible matter near hot parts',
  ),
  51 => 
  array (
    'key' => 'i052',
    'label' => 'Resistor units are supported and do not vibrate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Resistor units are supported and do not vibrate',
  ),
  52 => 
  array (
    'key' => 'i053',
    'label' => 'Cab is provided with safety glazing glass',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Cab is provided with safety glazing glass',
  ),
  53 => 
  array (
    'key' => 'i054',
    'label' => 'Cab door swings out to open or slides rearward for slid…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Cab door swings out to open or slides rearward for sliding door type (door operation is restrained during machine operation',
  ),
  54 => 
  array (
    'key' => 'i055',
    'label' => 'Cab wipers are in good condition and operating',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Cab wipers are in good condition and operating',
  ),
  55 => 
  array (
    'key' => 'i056',
    'label' => 'A clear passenger way is provided from operator positio…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'A clear passenger way is provided from operator position to outside the cab',
  ),
  56 => 
  array (
    'key' => 'i057',
    'label' => 'Platform to cab walkway is skid resistance',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Platform to cab walkway is skid resistance',
  ),
  57 => 
  array (
    'key' => 'i058',
    'label' => 'Handhold steps are provided to facilitate access to the…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Handhold steps are provided to facilitate access to the cab',
  ),
  58 => 
  array (
    'key' => 'i059',
    'label' => 'Communication method is one of the following:',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'General Requirements:',
    'item' => 'Communication method is one of the following:
* Hand signals (standard chart is posted in visible location to the operator)
* Radio/ Telephone
* Bell signal
* Special signals (to be understood and agreed upon by operator and signal man)',
  ),
  59 => 
  array (
    'key' => 'i060',
    'label' => 'Wire rope clips are correctly located and secured',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Wire rope clips are correctly located and secured',
  ),
  60 => 
  array (
    'key' => 'i061',
    'label' => 'The rope does not have more than 6 randomly distributed…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'The rope does not have more than 6 randomly distributed broken wires in 1 lay or 3 in1 strand in 1 lay (for running rope)',
  ),
  61 => 
  array (
    'key' => 'i062',
    'label' => 'The rope does not have broken outer wires at the contac…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'The rope does not have broken outer wires at the contact point with the rope core (for running rope)',
  ),
  62 => 
  array (
    'key' => 'i063',
    'label' => 'The rope wear does not exceed 1/3 of the original diame…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'The rope wear does not exceed 1/3 of the original diameter (for running rope)',
  ),
  63 => 
  array (
    'key' => 'i064',
    'label' => 'The rope does not have kinking, crushing, bird caging, …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'The rope does not have kinking, crushing, bird caging, evidence of heat damage, upstanding, core corrosion, main strand displacement or any other damages',
  ),
  64 => 
  array (
    'key' => 'i065',
    'label' => 'The rope does not have more than two broken wires in 1 …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'The rope does not have more than two broken wires in 1 lay in sections beyond end connection or more than 1 broken wire at an end connection (for standing ropes)',
  ),
  65 => 
  array (
    'key' => 'i066',
    'label' => 'Wire rope is not corroded',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Wire rope is not corroded',
  ),
  66 => 
  array (
    'key' => 'i067',
    'label' => 'Rope lubrication is adequate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Rope lubrication is adequate',
  ),
  67 => 
  array (
    'key' => 'i068',
    'label' => 'Sheaves and drums do not crack or show worn surfaces',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Sheaves and drums do not crack or show worn surfaces',
  ),
  68 => 
  array (
    'key' => 'i069',
    'label' => 'Bolts and rivets around the structure are tight',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Bolts and rivets around the structure are tight',
  ),
  69 => 
  array (
    'key' => 'i070',
    'label' => 'Clutch system parts, lining, pawls and ratchet are not …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Clutch system parts, lining, pawls and ratchet are not excessively worn',
  ),
  70 => 
  array (
    'key' => 'i071',
    'label' => 'Brake system parts are not excessively worn',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Brake system parts are not excessively worn',
  ),
  71 => 
  array (
    'key' => 'i072',
    'label' => 'Rope reeving is Compiled to manufacturer\'s recommendati…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Rope reeving is Compiled to manufacturer\'s recommendations',
  ),
  72 => 
  array (
    'key' => 'i073',
    'label' => 'Sheave sizes are not less than 16 times the nominal rop…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Sheave sizes are not less than 16 times the nominal rope diameter for boom hoist',
  ),
  73 => 
  array (
    'key' => 'i074',
    'label' => 'Sheave sizes are not less than 18 times the nominal rop…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Sheave sizes are not less than 18 times the nominal rope diameter for load hoist',
  ),
  74 => 
  array (
    'key' => 'i075',
    'label' => 'Eye splices are in accordance with the manufacturer\'s r…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Eye splices are in accordance with the manufacturer\'s recommendation and rope thimbles are used',
  ),
  75 => 
  array (
    'key' => 'i076',
    'label' => 'Load block sheaves shall have pitch diameters not less …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Load block sheaves shall have pitch diameters not less than 16 times the nominal diameter of the rope used',
  ),
  76 => 
  array (
    'key' => 'i077',
    'label' => 'Breaks and Cutches are provided with adjustments to com…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Breaks and Cutches are provided with adjustments to compensate for wear',
  ),
  77 => 
  array (
    'key' => 'i078',
    'label' => 'Boom hoist drum is provided with an auxiliary a locking…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Boom hoist drum is provided with an auxiliary a locking device controllable from the operator\'s station to hold the drum from rotating in the lowering direction and to hold the rated load',
  ),
  78 => 
  array (
    'key' => 'i079',
    'label' => 'Boom hoist drum has at least 2 full wraps remaining on …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'Boom hoist drum has at least 2 full wraps remaining on the drum when the boom is at lowest position',
  ),
  79 => 
  array (
    'key' => 'i080',
    'label' => 'The load hoist mechanism is provided with a suitable cl…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOISTING COMPONENTS:',
    'item' => 'The load hoist mechanism is provided with a suitable clutching or power engaging device unless directly coupled',
  ),
  80 => 
  array (
    'key' => 'i081',
    'label' => 'All welded members and joints have no cracks, distortio…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'All welded members and joints have no cracks, distortion, corrosion or other defects',
  ),
  81 => 
  array (
    'key' => 'i082',
    'label' => 'Boom stops are provided and in good condition (one of t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Boom stops are provided and in good condition (one of the following fixed or
telescopic bumper. Shock absorbing umber, hydraulic boom elevation cylinder or derrick masts)',
  ),
  82 => 
  array (
    'key' => 'i083',
    'label' => 'Boom angle indicators are provided and readable from op…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Boom angle indicators are provided and readable from operator\'s cabin',
  ),
  83 => 
  array (
    'key' => 'i084',
    'label' => 'Boom hoists disconnect; shutoff or hydraulic relief is …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Boom hoists disconnect; shutoff or hydraulic relief is provided to stop the boom hoist automatically when the boom reaches a predetermined angle',
  ),
  84 => 
  array (
    'key' => 'i085',
    'label' => 'Cord and lacing have no damage',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Cord and lacing have no damage',
  ),
  85 => 
  array (
    'key' => 'i086',
    'label' => 'Guy ropes are correctly tensioned',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Guy ropes are correctly tensioned',
  ),
  86 => 
  array (
    'key' => 'i087',
    'label' => 'Derrick fittings and connections are Comply to the OEM …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Derrick fittings and connections are Comply to the OEM recommendations',
  ),
  87 => 
  array (
    'key' => 'i088',
    'label' => 'Clutch system parts, linings, pawls and ratchet are not…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Clutch system parts, linings, pawls and ratchet are not excessively worn',
  ),
  88 => 
  array (
    'key' => 'i089',
    'label' => 'Load, boom angle and other indicators over their full r…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Load, boom angle and other indicators over their full range are accurately functioning',
  ),
  89 => 
  array (
    'key' => 'i090',
    'label' => 'Chain drive sprockets do not show excessive wear and ar…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Chain drive sprockets do not show excessive wear and are not stretched',
  ),
  90 => 
  array (
    'key' => 'i091',
    'label' => 'Gudgeon pins do not have cracks wear or distortion',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Gudgeon pins do not have cracks wear or distortion',
  ),
  91 => 
  array (
    'key' => 'i092',
    'label' => 'Supports do not have any defects and have continued abi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Supports do not have any defects and have continued ability to sustain the imposed loads',
  ),
  92 => 
  array (
    'key' => 'i093',
    'label' => 'Hydraulic and pneumatic hoses, fittings and tubes are n…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic and pneumatic hoses, fittings and tubes are not damaged and not leaking',
  ),
  93 => 
  array (
    'key' => 'i094',
    'label' => 'Hydraulic/Pneumatic pumps and motors are correctly func…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic/Pneumatic pumps and motors are correctly functioning',
  ),
  94 => 
  array (
    'key' => 'i095',
    'label' => 'Hydraulic/Pneumatic pumps and motors have no leaks, exc…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic/Pneumatic pumps and motors have no leaks, excess vibrations, unusual noises, loss of pressure or loss of speed',
  ),
  95 => 
  array (
    'key' => 'i096',
    'label' => 'Hydraulic/Pneumatic valves and motors are correctly fun…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic/Pneumatic valves and motors are correctly functioning',
  ),
  96 => 
  array (
    'key' => 'i097',
    'label' => 'Hydraulic/Pneumatic valves and motors have no leaks, ex…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic/Pneumatic valves and motors have no leaks, excess vibrations, unusual noises, loss of pressure or loss of pressure',
  ),
  97 => 
  array (
    'key' => 'i098',
    'label' => 'Hydraulic/Pneumatic cylinders do not leak (external and…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic/Pneumatic cylinders do not leak (external and internal) at seals and welded joints or any other locations',
  ),
  98 => 
  array (
    'key' => 'i099',
    'label' => 'Hydraulic/Pneumatic cylinders are free of scores, dents…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic/Pneumatic cylinders are free of scores, dents or nicks on piston rods or cylinder barrels and loose/deformed rod eyes or connecting joints',
  ),
  99 => 
  array (
    'key' => 'i100',
    'label' => 'Hydraulic filters are clean and free from debris such a…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Hydraulic filters are clean and free from debris such as rubber or metal particles',
  ),
  100 => 
  array (
    'key' => 'i101',
    'label' => 'Boom cradle is available and can secure the boom if req…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'BOOM COMPONENTS:',
    'item' => 'Boom cradle is available and can secure the boom if required',
  ),
  101 => 
  array (
    'key' => 'i102',
    'label' => 'Pins, bearings, shafts, gears, rollers and locking devi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'SWING COMPONENTS:',
    'item' => 'Pins, bearings, shafts, gears, rollers and locking devices are free of wear, crack and distortion',
  ),
  102 => 
  array (
    'key' => 'i103',
    'label' => 'The swing mechanism controls the swing of the rated loa…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'SWING COMPONENTS:',
    'item' => 'The swing mechanism controls the swing of the rated load under all operating conditions',
  ),
  103 => 
  array (
    'key' => 'i104',
    'label' => 'Swing braking operates and is capable of restrict the m…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'SWING COMPONENTS:',
    'item' => 'Swing braking operates and is capable of restrict the movement of the rotating structure',
  ),
  104 => 
  array (
    'key' => 'i105',
    'label' => 'A swing brake locking device is provided and capable of…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'SWING COMPONENTS:',
    'item' => 'A swing brake locking device is provided and capable of locking the rotation of rotating structure',
  ),
  105 => 
  array (
    'key' => 'i106',
    'label' => 'Swing brakes are adjustable to compensate for wear',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'SWING COMPONENTS:',
    'item' => 'Swing brakes are adjustable to compensate for wear',
  ),
  106 => 
  array (
    'key' => 'i107',
    'label' => 'All swing moving parts are lubricated',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'SWING COMPONENTS:',
    'item' => 'All swing moving parts are lubricated',
  ),
  107 => 
  array (
    'key' => 'i108',
    'label' => 'Exhaust pipes are insulated, and gases are properly dis…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'OPERATOR\'S CABIN:',
    'item' => 'Exhaust pipes are insulated, and gases are properly discharged from the operator\'s cab.',
  ),
  108 => 
  array (
    'key' => 'i109',
    'label' => 'A portable fire extinguisher is in place inside the cab…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'OPERATOR\'S CABIN:',
    'item' => 'A portable fire extinguisher is in place inside the cab and outside machinery (min rating of 10 BC)',
  ),
  109 => 
  array (
    'key' => 'i110',
    'label' => 'A toolbox with basic maintenance tools is placed inside…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'OPERATOR\'S CABIN:',
    'item' => 'A toolbox with basic maintenance tools is placed inside the cab',
  ),
  110 => 
  array (
    'key' => 'i111',
    'label' => 'An audible warning device is in place and within reach …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'OPERATOR\'S CABIN:',
    'item' => 'An audible warning device is in place and within reach of the operator',
  ),
  111 => 
  array (
    'key' => 'i112',
    'label' => 'ASLI is installed and properly working',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'OPERATOR\'S CABIN:',
    'item' => 'ASLI is installed and properly working',
  ),
  112 => 
  array (
    'key' => 'i113',
    'label' => 'The cab and operating enclosures have good housekeeping.',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'OPERATOR\'S CABIN:',
    'item' => 'The cab and operating enclosures have good housekeeping.',
  ),
  113 => 
  array (
    'key' => 'i114',
    'label' => 'The machinery and electrical equipment are located clea…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'MACHINERY & ELECTRICAL EQUIPMENT:',
    'item' => 'The machinery and electrical equipment are located clear of deck loading areas',
  ),
  114 => 
  array (
    'key' => 'i115',
    'label' => 'Work areas, companion ways and ladder are equipped with…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'MACHINERY & ELECTRICAL EQUIPMENT:',
    'item' => 'Work areas, companion ways and ladder are equipped with anti-slip surface materials',
  ),
  115 => 
  array (
    'key' => 'i116',
    'label' => 'Electrical wiring and equipment are free of damage and …',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'MACHINERY & ELECTRICAL EQUIPMENT:',
    'item' => 'Electrical wiring and equipment are free of damage and of the specified type for shipboard',
  ),
  116 => 
  array (
    'key' => 'i117',
    'label' => 'Manholes hatches are not less than 15"x22" and raised a…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'MACHINERY & ELECTRICAL EQUIPMENT:',
    'item' => 'Manholes hatches are not less than 15"x22" and raised above the deck to prevent accidental entry of spilled liquids',
  ),
  117 => 
  array (
    'key' => 'i118',
    'label' => 'Labelling and manufacturing data are available and legi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Labelling and manufacturing data are available and legible',
  ),
  118 => 
  array (
    'key' => 'i119',
    'label' => 'Hook\'s weight is clearly marked/ printed on the hook',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Hook\'s weight is clearly marked/ printed on the hook',
  ),
  119 => 
  array (
    'key' => 'i120',
    'label' => 'Safe working load is clearly marked on the hook',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Safe working load is clearly marked on the hook',
  ),
  120 => 
  array (
    'key' => 'i121',
    'label' => 'Hook is not bent or twisted. Max. bending or twisting n…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Hook is not bent or twisted. Max. bending or twisting not to exceed 10 degrees from plane of unbent hook or asper OEM recommendations',
  ),
  121 => 
  array (
    'key' => 'i122',
    'label' => 'Maximum wear in the hook bowl is not exceeding 10% (com…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Maximum wear in the hook bowl is not exceeding 10% (compared to new hook) or asper OEM recommendations',
  ),
  122 => 
  array (
    'key' => 'i123',
    'label' => 'Hook is not cracked',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Hook is not cracked',
  ),
  123 => 
  array (
    'key' => 'i124',
    'label' => 'Hook can lock (if it is a self-locking hook)',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Hook can lock (if it is a self-locking hook)',
  ),
  124 => 
  array (
    'key' => 'i125',
    'label' => 'Hook latch is operative',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Hook latch is operative',
  ),
  125 => 
  array (
    'key' => 'i126',
    'label' => 'Hook is free to rotate',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'HOOKS:',
    'item' => 'Hook is free to rotate',
  ),
  126 => 
  array (
    'key' => 'i127',
    'label' => 'Verification of all functions',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => 'FUNCTIONAL TEST:',
    'item' => 'Verification of all functions',
  ),
);
$head = [
    ['key' => 'client', 'label' => 'Client Name', 'source' => 'client.name'],
    ['key' => 'site', 'label' => 'Location / Vessel', 'source' => 'job.location'],
    ['key' => 'report_no', 'label' => 'Certificate No.', 'source' => 'cert.certificate_no'],
    ['key' => 'inspection_date', 'label' => 'Inspection Date', 'source' => 'cert.inspection_date'],
    ['key' => 'crane_type', 'label' => 'Crane Type', 'type' => 'text', 'required' => true],
    ['key' => 'capacity', 'label' => 'Capacity / SWL', 'source' => 'equipment.capacity'],
    ['key' => 'serial', 'label' => 'Identification No. S/N', 'source' => 'equipment.serial_no'],
    ['key' => 'manufacturer_year', 'label' => 'Manufacturer / Year', 'type' => 'text', 'required' => true],
    ['key' => 'tag_no', 'label' => 'Equipment Tag No', 'source' => 'equipment.equipment_no'],
    ['key' => 'boom_length', 'label' => 'Min. / Max. Boom Length', 'type' => 'text', 'required' => false],
    ['key' => 'model', 'label' => 'Crane Model', 'source' => 'equipment.model'],
    ['key' => 'radius', 'label' => 'Min. / Max. Radius', 'type' => 'text', 'required' => false],
    ['key' => 'boom_rope', 'label' => 'Boom Hoist Wire Rope Specs', 'type' => 'text', 'required' => false],
    ['key' => 'main_rope', 'label' => 'Main Hoist Wire Rope Specs', 'type' => 'text', 'required' => false],
    ['key' => 'pendant_rope', 'label' => 'Pendant Wire Rope Specs', 'type' => 'text', 'required' => false],
    ['key' => 'aux_rope', 'label' => 'Aux Hoist Wire Rope Specs', 'type' => 'text', 'required' => false],
    ['key' => 'last_exam', 'label' => 'Date of Last Examination (Blue Sticker)', 'type' => 'text', 'required' => false],
    ['key' => 'next_exam', 'label' => 'Date of Next Examination (Blue Sticker)', 'source' => 'cert.next_inspection_date'],
];
return [
    'code' => 'F-7.5.1-CL 029', 'title' => 'CRANE HEALTH CHECK', 'doc_ref' => 'F-7.5.1-CL 029',
    'revision' => '02', 'revision_date' => '09/Feb./23', 'layout' => 'ch', 'inspector_label' => 'Inspector Name:',
    'default_statement' => '',
    'fields' => array_merge($head, $items),
    'notes' => '',
];