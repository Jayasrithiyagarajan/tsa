<?php
// F-7.5.1-14 Motorcycle report as a certificate template: header fields + one Pass/Fail/N/A select per checklist item.
// 'section' / 'item' keep the bilingual form text used by documents/certificate_mo.php.
$items = array (
  0 => 
  array (
    'key' => 'i1_1',
    'label' => '1.1 The engraved VIN number of the Motorcycle on the body…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '1|Motorcycle VIN|الرقم المميز للدراجة',
    'item' => '@1.1
فحص الرقم المميز للدراجة المحفور على هيكــــل الدراجة للتأكد من مطابقته مع وثيقة الدراجة
Check the engraved VIN number of the Motorcycle on the body to ensure that it matches the Motorcycle document',
  ),
  1 => 
  array (
    'key' => 'i1_2',
    'label' => '1.2 The location and fixation of the VIN number of the Mo…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '1|Motorcycle VIN|الرقم المميز للدراجة',
    'item' => '@1.2
التأكد من مكان وتثبيت الرقم المميز للدراجة طبقا للمواصفة القياسية السعودية رقم
SASO GSO 1782
Ensure the location and fixation of the VIN number of the Motorcycle in accordance with SASO GSO 1782',
  ),
  2 => 
  array (
    'key' => 'i1_3',
    'label' => '1.3 The Motorcycle engraved VIN number on the body is cle…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '1|Motorcycle VIN|الرقم المميز للدراجة',
    'item' => '@1.3
التأكد من أن الرقم المميز للدراجة المحفور على جسم الدراجة مرئي بوضوح وقابل للقراءة بسهولة ولم يتم التعديل عليه
Ensuring that the Motorcycle engraved VIN number on the body is clearly visible and easily readable',
  ),
  3 => 
  array (
    'key' => 'i2_1',
    'label' => '2.1 The following information shall be riveted or permane…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '2||',
    'item' => '@2.1
يجب التأكد من وجود البيانات التالية في مكان بارز على الهيكل و تكون مثبتة بشكل دائم
وبطريقـة لا يمكن معها إزالتها أو إتلافها
The following information shall be riveted or permanently affixed in:
such a manner that it cannot be removed without being destroyed',
  ),
  4 => 
  array (
    'key' => 'i2_1_1',
    'label' => '2.1.1 Name of manufacturer',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '2||',
    'item' => '@2.1.1
اسم الجهة الصانعة.
Name of manufacturer',
  ),
  5 => 
  array (
    'key' => 'i2_1_2',
    'label' => '2.1.2 Year of production',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '2||',
    'item' => '@2.1.2
سنة الإنتاج.
Year of production',
  ),
  6 => 
  array (
    'key' => 'i2_1_3',
    'label' => '2.1.3 Motorcycle identification number',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '2||',
    'item' => '@2.1.3
الرقم المميز للدراجة النارية.
Motorcycle identification number',
  ),
  7 => 
  array (
    'key' => 'i2_1_4',
    'label' => '2.1.4 Engine capacity',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '2||',
    'item' => '@2.1.4
سعة المحرك
Engine capacity',
  ),
  8 => 
  array (
    'key' => 'i3_1',
    'label' => '3.1 The Motorcycle compatible to the safety requirements…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.1
التأكد من مطابقة الدراجة لاشتراطات السلامة من حيث وجود إطارات وأنوار وفرامل و مرايا و إشارات تحذيرية تعمل بشكل سليم كما وردت من الشركة المصنعة.
Ensure that the Motorcycle compatible to the safety requirements in terms of the tires, lights, brakes, mirrors and warning signs that
work properly as received from the manufacturer',
  ),
  9 => 
  array (
    'key' => 'i3_2',
    'label' => '3.2 No defects in motorcycle horn like it is loose it has…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.2
التأكد من عدم وجود عيوب ظاهرة في أداة التنبيه الصوتية )البوق( للدراجة النارية مثل ألا تعمل أو مرتخية التثبيت أو تصدر اصواتاً موسيقية أو متعددة
Ensure that, no defects in motorcycle horn like it is loose it has musical sounds or multiple sounds',
  ),
  10 => 
  array (
    'key' => 'i3_3',
    'label' => '3.3 The side pivot base or the middle is not missing or w…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.3
التأكد من أن قاعدة الارتكاز الجانبية أو الوسطى للدراجة غير مفقودة أو لا تعمل.
Ensure that the side pivot base or the middle is not missing or working',
  ),
  11 => 
  array (
    'key' => 'i3_4',
    'label' => '3.4 No difference in straightening the front wheel of the…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.4
التأكد من عدم اختلاف استقامة العجلة الأمامية للدراجة النارية بالمقارنة مع العجلة الخلفية Ensure that, No difference in straightening the front wheel of the motorcycle comparing to the back wheel .',
  ),
  12 => 
  array (
    'key' => 'i3_5',
    'label' => '3.5 The handlebar is loose, or it is broken or not stable…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.5
التأكد من عدم ارتخاء الذراع المتغير للدراجة أو مسمار المحور أو تأكل الجلب.
Ensure that, the handlebar is loose, or it is broken or not stable or bushing damage',
  ),
  13 => 
  array (
    'key' => 'i3_6',
    'label' => '3.6 No Oil leakage in the shock absorber (in the front or…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.6
التأكد من عدم تهريب زيت من ممتص الصدمات الأمامية أو الخلفية
Ensure that, No Oil leakage in the shock absorber (in the front or in the back)',
  ),
  14 => 
  array (
    'key' => 'i3_7',
    'label' => '3.7 The shift between gears is no more than 6 gears',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '3|External inspection|الفحص الخارجي',
    'item' => '@3.7
التأكد من ان الانتقال بين السرعات لا يزيد عن 6 نقلات للسرعة
Ensure that, the shift between gears is no more than 6 gears',
  ),
  15 => 
  array (
    'key' => 'i4_1',
    'label' => '4.1 The Motorcycle handlebar is straight and that there a…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4|Handlebar|مقود الدراجة',
    'item' => '@4.1
التأكد من استقامة مقود الدراجة وعدم وجود أعمال إصلاح تؤثر على ثباته
Ensure that, the Motorcycle handlebar is straight and that there are no repairs that affect its stability',
  ),
  16 => 
  array (
    'key' => 'i4_2',
    'label' => '4.2 There is a clearance in the handlebar either vertical…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4|Handlebar|مقود الدراجة',
    'item' => '@4.2
التأكد من عدم وجود خلوص ظاهرة بمقود الدراجة سواء خلوص عمودية أو أفقية
Ensure that, there is a clearance in the handlebar either vertical or Horizontal',
  ),
  17 => 
  array (
    'key' => 'i4_3',
    'label' => '4.3 Modifications (not intended for the motorcycle) on th…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4|Handlebar|مقود الدراجة',
    'item' => '@4.3
التأكد من عدم وجود تعديل غير مخصص لاستخدام الدراجة على أذرع التوجيه
Ensure that, Modifications (not intended for the motorcycle) on the Steering rods',
  ),
  18 => 
  array (
    'key' => 'i4_4',
    'label' => '4.4 The handlebar is loose, or it is broken or not stable',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4|Handlebar|مقود الدراجة',
    'item' => '@4.4
التأكد من عدم وجود ارتخاء أو كسور أو عدم ثبات بمقود الدراجة
Ensure that the handlebar is loose, or it is broken or not stable',
  ),
  19 => 
  array (
    'key' => 'i4_5',
    'label' => '4.5 The existence of welding work on handlebar or steerin…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4|Handlebar|مقود الدراجة',
    'item' => '@4.5
التأكد من عدم وجود أعمال لحام في مقود الدراجة أو أذرع التوجيه
Ensure that, The existence of welding work on handlebar or steering rods',
  ),
  20 => 
  array (
    'key' => 'i4_6',
    'label' => '4.6 The ends of the handlebars shall be rounded',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4|Handlebar|مقود الدراجة',
    'item' => '@4.6
التأكد من أن أطراف مقود الدراجة النارية مستديرة
Ensure that the ends of the handlebars shall be rounded',
  ),
  21 => 
  array (
    'key' => 'i5_1',
    'label' => '5.1 The brakes shall be capable either of being operated…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5|Brakes|المكابح',
    'item' => '@5.1
التأكد من أن تكون أدوات/أجهزة المكابح قادرة على العمل بشكل مستقل.
Ensure that, the brakes shall be capable either of being operated independently',
  ),
  22 => 
  array (
    'key' => 'i5_2',
    'label' => '5.2 The front brake lever shall be on the right-side hand…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5|Brakes|المكابح',
    'item' => '@5.2
التأكد من أن ذراع المكابح الأمامي على قضيب المقبض الأيمن وذراع المكابح الخلفي إما على المقود الجانبي الأيسر أو يتم تشغيله بالقدم اليمنى .
Ensure that, the front brake lever shall be on the right-side handle
bar and the rear brake lever either on the left side handlebar or operated by the right foot. to ensure safe driving',
  ),
  23 => 
  array (
    'key' => 'i5_3',
    'label' => '5.3 Brakes systems shall be of disk brake with pad system…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5|Brakes|المكابح',
    'item' => '@5.3
التأكد من أن قوة مكابح الدراجة مناسبة أو غير ضعيفة
Ensure that Brakes systems shall be of disk brake with pad system or Drum brake with lining system',
  ),
  24 => 
  array (
    'key' => 'i5_4',
    'label' => '5.4 Brake handle is broken, or the brake pedal is having…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5|Brakes|المكابح',
    'item' => '@5.4
التأكد من عدم وجود كسور أو تحطم بمقبض المكابح أو عدم وجود ضربات بدواسة المكابح
Ensure that, Brake handle is broken, or the brake pedal is having some hits',
  ),
  25 => 
  array (
    'key' => 'i5_5',
    'label' => '5.5 The brakes are in accordance with SASO GSO ECE 78',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5|Brakes|المكابح',
    'item' => '@5.5
SASO GSO ECE 78 التأكد من أن المكابح طبقاً للمواصفة القياسية السعودية Ensure that, the brakes are in accordance with SASO GSO ECE 78',
  ),
  26 => 
  array (
    'key' => 'i6_1',
    'label' => '6.1 The battery provided shall be suitable for the motorc…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '6|Battery|البطارية',
    'item' => '@6.1
التأكد من أن البطارية المتوفرة مناسبة لدورة المحرك ويجب ألا تقل عن 12 فولت.
Ensure that, The battery provided shall be suitable for the motorcycle and it shall be not less than 12 V',
  ),
  27 => 
  array (
    'key' => 'i6_2',
    'label' => '6.2 The battery shall be shielded in a special container,…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '6|Battery|البطارية',
    'item' => '@6.2
التأكد من أن البطارية محمية في حاوية خاصة بحيث لا تتعرض للحرارة والاتربة وأشعة الشمس
Ensure that, The battery shall be shielded in a special container, so that it will not be exposed to heat, dust and sunlight',
  ),
  28 => 
  array (
    'key' => 'i6_3',
    'label' => '6.3 The engine shut-off control shall be clearly marked e…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '6|Battery|البطارية',
    'item' => '@6.3
التأكد من أن نظام التحكم في إيقاف تشغيل المحرك محددًا بوضوح للتحكم في إيقاف تشغيل المحرك ويمكن الوصول إليه من قبل الراكب الجالس .يجب أن يتطلب التحكم إجراءً إضافيًا
لإعادة التعيين بعد الاستخدام
Ensure that The engine shut-off control shall be clearly marked engine shut-off control accessible by a seated occupant. The control shall require an additional action to reset after use',
  ),
  29 => 
  array (
    'key' => 'i7_1',
    'label' => '7.1 It is of metal or any other suitable material',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7|Fuel Tank|خزان الوقود',
    'item' => '@7.1
التأكد من أن يكون من المعدن أو أي مادة أخرى مناسبة
Ensure that it is of metal or any other suitable material',
  ),
  30 => 
  array (
    'key' => 'i7_2',
    'label' => '7.2 It is adequately protected or designed to protect aga…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7|Fuel Tank|خزان الوقود',
    'item' => '@7.2
التأكد من أن يكون محمياً أو مصمماً بشكل ملائم للحماية ضد أي ضرر من الحادث
Ensure that it is adequately protected or designed to protect against any damage from the accident',
  ),
  31 => 
  array (
    'key' => 'i7_3',
    'label' => '7.3 The filler neck shall be at least 25 mm in diameter a…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7|Fuel Tank|خزان الوقود',
    'item' => '@7.3
التأكد من أن يكون قطر فتحة الشحن 25 مم على الأقل وأن تسمح برؤية مستوى الوقود من السطح الخارجي .
Ensure that, the filler neck shall be at least 25 mm in diameter and it shall allow sight of the fuel level to avoid over-filling',
  ),
  32 => 
  array (
    'key' => 'i7_4',
    'label' => '7.4 The cap shall have a securing strap to avoid loss dur…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7|Fuel Tank|خزان الوقود',
    'item' => '@7.4
التأكد من أن يكون غطاء الخزان مجهز برباط أو سلسة لتجنب فقده أثناء التزود بالوقود في
حال كان الغطاء بدون مفتاح
Ensure that, The cap shall have a securing strap to avoid loss during refilling, In case of fuel cap without key',
  ),
  33 => 
  array (
    'key' => 'i7_5',
    'label' => '7.5 The filler cap shall positively lock shut',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7|Fuel Tank|خزان الوقود',
    'item' => '@7.5
ا لتأكد من أن غطاء الخزان قابلاً للإغلاق بإحكام
Ensure that The filler cap shall positively lock shut',
  ),
  34 => 
  array (
    'key' => 'i8_1',
    'label' => '8.1 The motorcycle shall be provided with a speedometer d…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8|Speedometer|عداد السرعة',
    'item' => '@8.1
التأكد من أن الدراجة النارية مجهزة بمقياس مدرج أو رقمي لعداد السرعة.
Ensure that the motorcycle shall be provided with a speedometer dial gauge or digital gauge',
  ),
  35 => 
  array (
    'key' => 'i8_2',
    'label' => '8.2 The dial of the speedometer shall be located within t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8|Speedometer|عداد السرعة',
    'item' => '@8.2
التأكد من أن العداد ضمن مجال الرؤية المباشر للسائق ويمكن قراءته بسهولة في الليل والنهار
Ensure that, the dial of the speedometer shall be located within the
direct field of view of the driver and shall be clearly legible both day and night',
  ),
  36 => 
  array (
    'key' => 'i8_3',
    'label' => '8.3 Where the maximum speed on the dial does not exceed 2…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8|Speedometer|عداد السرعة',
    'item' => '@8.3
في حالة إذ ا كان عداد السرعة يشتمل على مقياس مدرج بدلا عن المعلومات الرقمية يجب التأكد من أن العلامات المدرجة لتحديد أرقام السرعة موزعة على فترات لا تتجاوز 20 كم/ساعة في حال كانت السرعة لا تتجاوز ( 002 )كم/ساعة
Ensure that, Where the maximum speed on the dial does not exceed
200 km/h, the marked numerical speed value intervals shall not exceed 20 km/h',
  ),
  37 => 
  array (
    'key' => 'i8_4',
    'label' => '8.4 Where the maximum speed on dial exceeds 200 km/h, the…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8|Speedometer|عداد السرعة',
    'item' => '@8.4
في حالة إذ ا كان عداد السرعة يشتمل على مقياس مدرج بدلا عن المعلومات الرقمية يجب التأكد من أن العلامات المدرجة لتحديد أرقام السرعة موزعة على فترات لا تتجاوز 30 كم/ساعة في حال كانت السرعة تتجاوز ( 002 )كم/ساعة
Ensure that, Where the maximum speed on dial exceeds 200 km/h,
the marked numerical speed value intervals shown on dial must not exceed 30 km/h',
  ),
  38 => 
  array (
    'key' => 'i8_5',
    'label' => '8.5 In the case of speedometers intended for mopeds, the…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8|Speedometer|عداد السرعة',
    'item' => '@8.5
في حالة عدادات السرعة المخصصة للدراجات ذات المحركات الصغيرة يجب أن يوضح المقياس المدرج السرعة القصوى بحيث لا تتجاوز ( 08 ) كلم/ساعة.
Ensure that, In the case of speedometers intended for mopeds, the dial shall indicate a maximum speed not exceeding 80 km/h',
  ),
  39 => 
  array (
    'key' => 'i8_6',
    'label' => '8.6 The subdivisions shall be of 1, 2, 5 or 10 km/h and t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8|Speedometer|عداد السرعة',
    'item' => '@8.6
التأكد من أن تكون التقسيمات الفرعية من 1 أو 2 أو 5 أو 10 كم/ ساعة ويجب ألا تتجاوز فترات قيمة السرعة الموضحة على القرص 10كم / ساعة.
Ensure that The subdivisions shall be of 1, 2, 5 or 10 km/h and the speed value intervals shown on the dial shall not exceed 10 km/h',
  ),
  40 => 
  array (
    'key' => 'i9_1',
    'label' => '9.1 There are no visible defects in the front or rear lig…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9|Lights|الأنوار',
    'item' => '@9.1
التأكد من عدم وجود عيوب ظاهرة في أنوار و إشارات الدراجة الأمامية أو الخلفية مثل أن . )تكون مفقودة أو مكسورة أو لا تعمل أو غير مثبتة جيداً) مرتخية
Ensure that there are no visible defects in the front or rear lights and signals of the bicycle, such as missing, broken, non-functional, or not properly installed (loose)',
  ),
  41 => 
  array (
    'key' => 'i9_2',
    'label' => '9.2 The head lamp shall be powerful and capable of allowi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9|Lights|الأنوار',
    'item' => '@9.2
التأكد من أن تكون المصابيح )الأنوار( الأمامية قوية وشديدة الإضاءة وقادرة على السماح برؤية أي عوائق على الطريق بمسافة 100 م بسهولة
Ensure that, The head lamp shall be powerful and capable of allowing any obstacle on the way at distance of 100 m to be .recognized easily',
  ),
  42 => 
  array (
    'key' => 'i9_3',
    'label' => '9.3 Two side turn signal lamps on each side shall be .pro…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9|Lights|الأنوار',
    'item' => '@9.3
التأكد من وجود أنوار الإشارة على جانبي الدراجة النارية بحيث يوفر أثنين في الأمام وأثنين في المؤخرة
Ensure that, two side turn signal lamps on each side shall be
.provided. Two in front and two at the rear',
  ),
  43 => 
  array (
    'key' => 'i9_4',
    'label' => '9.4 An emergency flash lamp in amber color shall be provi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9|Lights|الأنوار',
    'item' => '@9.4
التأكد من توفر أنوار الطوارئ ذات اللون الكهرماني بالدراجة النارية.
Ensure that, An emergency flash lamp in amber color shall be provided',
  ),
  44 => 
  array (
    'key' => 'i9_5',
    'label' => '9.5 The rear number plate light shall be provided to the…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9|Lights|الأنوار',
    'item' => '@9.5
التأكد من توفر لمبة لوحة الأرقام الخلفية بالدراجة النارية بحيث تكون واضحة للرؤية من مسافة 20 مترًا للخلف.
Ensure that, the rear number plate light shall be provided to the motorcycle at distance of 20 m from the back',
  ),
  45 => 
  array (
    'key' => 'i9_6',
    'label' => '9.6 The lights provided shall comply with the European st…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9|Lights|الأنوار',
    'item' => '@9.6
يجب أن تكون الانوار طبقاً للمواصفة الأوروبية ECE 50
Ensure that, The lights provided shall comply with the European standard No. ECE 50',
  ),
  46 => 
  array (
    'key' => 'i10_1',
    'label' => '10.1 The exhaust system shall be suitably designed or cov…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '10|Exhaust system|نظام العادم',
    'item' => '@10.1
التأكد من أن تصميم العادم )الشكمان( بالشكل المناسب والتغطية اللازمة لتجنيب السائق أو الركاب من التعرض لأي إصابات ناجمة عن الحروق.
Ensure that, The exhaust system shall be suitably designed or covered to avoid any burn injury to the driver or passengers',
  ),
  47 => 
  array (
    'key' => 'i10_2',
    'label' => '10.2 The end (s) of exhaust pipe (s) shall not be located…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '10|Exhaust system|نظام العادم',
    'item' => '@10.2
التأكد من أن لا توضع نهايات مواسير العادم بشكل بارز عند جوانب الدراجة النارية.
Ensure that, The end (s) of exhaust pipe (s) shall not be located as the most out-said point (prominent) of motorcycle',
  ),
  48 => 
  array (
    'key' => 'i11_1',
    'label' => '11.1 The sound level of the motorcycle shall not exceed t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11|Noise emission|التلوث الضوضائي',
    'item' => '@11.1
التأكد من أن مستوى الضوضاء بالدراجة النارية لا يتجاوز القيم المذكورة بالمواصفة القياسية السعودية SASO GSO 1798
Ensure that The sound level of the motorcycle shall not exceed the value as in SASO GSO 1798',
  ),
  49 => 
  array (
    'key' => 'i11_2',
    'label' => '11.2 The noise emission and its methods of test shall com…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11|Noise emission|التلوث الضوضائي',
    'item' => '@11.2
التأكد من تطابق انبعاث الضوضاء وطرق اختبارها مع المتطلبات المحددة في المواصفة SASO GSO ECE 41-2 القياسية السعودية
Ensure that, The noise emission and its methods of test shall comply with the requirements specified in the SASO GSO ECE 41-2.',
  ),
  50 => 
  array (
    'key' => 'i12_1',
    'label' => '12.1 These symbols shall stand out clearly against the ba…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '12|Controls, tell-tales and indicators|أجهزة التحكم وإشارات التنبيه والمؤشرات',
    'item' => '@12.1
التأكد من أن الرموز الخاصة بإشارات التنبيه والمؤشرات واضحة مقابل الخلفية بحيث تكون ساطعة على خلفية داكنة أو تكون داكنة على خلفية ساطعة.
Ensure that, These symbols shall stand out clearly against the background, either bright against dark or dark against bright',
  ),
  51 => 
  array (
    'key' => 'i12_2',
    'label' => '12.2 ECE R60 SASO GSO ISO 6727 – SASO GSO ISO 9021 Ensure…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '12|Controls, tell-tales and indicators|أجهزة التحكم وإشارات التنبيه والمؤشرات',
    'item' => '@12.2
يجب أن تكون التعريفات والرموز تبعاً للمواصفات القياسية السعودية
ECE R60 و المواصفة الأوروبية SASO GSO ISO 6727 – SASO GSO ISO 9021
Ensure that, The designation and identification of symbols shall be in accordance with the
SASO GSO ISO 6727 – SASO GSO ISO 9021 and European standard No. ECE R60',
  ),
  52 => 
  array (
    'key' => 'i13_1',
    'label' => '13.1 Mud guarding shall be fitted over each wheel, projec…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '13|Mudguard|رفرف العجلة',
    'item' => '@13.1
التأكد من وجود رفرف فوق كل عجلة بحيث تكون بارزة جانبيًا وراء الإطارات على كل جانب كما يجب امتداد الرفرف الخلفي لخط الملامسة الرأسي للعجلة الخلفية
Ensure that, Mud guarding shall be fitted over each wheel, projecting laterally beyond the tires on each side. The rear mud guarding shall extend to the vertical tangent to the rear wheel',
  ),
  53 => 
  array (
    'key' => 'i14_1',
    'label' => '14.1 The ends of the foot rests shall be rounded with a r…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14|Foot rest|مسند القدمين',
    'item' => '@14.1
التأكد من أن أطراف مسند القدمين مستديرة ومصقولة بنصف قطر ولا يقل عن (2 ) مم أو عمل الوقاية اللازمة لها بطريقة أخرى.
Ensure that, The ends of the foot rests shall be rounded with a radius of not less than 2 mm or otherwise protected',
  ),
  54 => 
  array (
    'key' => 'i15_1',
    'label' => '15.1 No defects in the tires of the motorcycle such as di…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '15|Motorcycle tire|الإطارات',
    'item' => '@15.1
التأكد من عدم وجود عيوب ظاهرة في إطارات الدراجة مثل اختلاف المقاس أو تآكل أو تلف الإطارات أو إصلاح مقطعي)رقعة تيوبلس( بجدار الإطار أو تشوه العجلة.
Ensure that, No defects in the tires of the motorcycle such as different size, corrosion or damage in the tires, (tubeless patch) inside the tire, wheels deformation',
  ),
  55 => 
  array (
    'key' => 'i15_2',
    'label' => '15.2 The tire shall be in accordance with the SASO GSO .E…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '15|Motorcycle tire|الإطارات',
    'item' => '@15.2
أن تكون الإطارات طبقاً للمواصفات القياسية السعودية SASO GSO ECE 75-1 , SASO GSO
ECE 75-2
Ensure that, The tire shall be in accordance with the SASO GSO
.ECE 75-1, SASO GSO ECE 75-2',
  ),
  56 => 
  array (
    'key' => 'i16_1',
    'label' => '16.1 The two-wheeled Motorcycle has a Stand bar to keep i…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '16|Motorcycle Stand bar|ساند الدراجة',
    'item' => '@16.1
يجب التأكد من وجود ساند للدراجة ذات العجلتين لإبقائها ثابتة في حالة الوقوف
Ensure that, the two-wheeled Motorcycle has a Stand bar to keep it stable when standing',
  ),
  57 => 
  array (
    'key' => 'i17_1',
    'label' => '17.1 The Motorcycle is equipped with two rear-view mirror…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17|Vision Mirrors|مرايا الرؤية',
    'item' => '@17.1
التأكد من أن الدراجة مزودة بمرآتين للرؤية الخلفية واحدة على اليمين و الأخرى على اليسار
Ensure that the Motorcycle is equipped with two rear-view mirrors, one on the right and the other on the left',
  ),
  58 => 
  array (
    'key' => 'i17_2',
    'label' => '17.2 No defect in the side mirrors such as to be broken,…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17|Vision Mirrors|مرايا الرؤية',
    'item' => '@17.2
التأكد من عدم وجود عيوب ظاهرة في المرايا الجانبية للدراجة النارية مثل أن تكون مفقودة أو مكسورة أو باهتة أو مرتخية التثبيت.
Ensure that, No defect in the side mirrors such as to be broken, missing, faded, or loose',
  ),
  59 => 
  array (
    'key' => 'i17_3',
    'label' => '17.3 The rear-view mirrors are adjustable',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17|Vision Mirrors|مرايا الرؤية',
    'item' => '@17.3
التأكد من أن مرايا الرؤية الخلفية قابلة للتعديل
Ensure that, the rear-view mirrors are adjustable Ensure that, the rear-view mirrors are adjustable',
  ),
  60 => 
  array (
    'key' => 'i17_4',
    'label' => '17.4 The mirrors are free of sharp edges',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17|Vision Mirrors|مرايا الرؤية',
    'item' => '@17.4
التأكد من أن المرايا خالية من الأطراف الحادة
Ensure that, The mirrors are free of sharp edges',
  ),
  61 => 
  array (
    'key' => 'i17_5',
    'label' => '17.5 The dimensions and area of the reflective part and t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Pass',
      1 => 'Fail',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17|Vision Mirrors|مرايا الرؤية',
    'item' => '@17.5
التأكد من أن أبعاد و مساحة الجزء العاكس و مجال الرؤية للمرايا طبقا للمواصفة القياسية
SASO GSO ECE 81 السعودية
Ensure that, the dimensions and area of the reflective part and the field of view of the mirrors are in accordance with the Saudi standard SASO GSO ECE 81',
  ),
);

$head = [
    ['key' => 'client', 'label' => 'Client Name', 'source' => 'client.name'],
    ['key' => 'cert_no', 'label' => 'Report No.', 'source' => 'cert.certificate_no'],
    ['key' => 'report_date', 'label' => 'Report Date', 'source' => 'cert.issue_date'],
    ['key' => 'insp_date', 'label' => 'Inspection Date', 'source' => 'cert.inspection_date'],
    ['key' => 'vin', 'label' => 'VIN Number (17 characters)', 'type' => 'text', 'required' => true],
    ['key' => 'brand', 'label' => 'Manufacture (Trademark)', 'source' => 'equipment.manufacturer'],
    ['key' => 'eq_desc', 'label' => 'Motorcycle Type (Description)', 'source' => 'equipment.description'],
    ['key' => 'color', 'label' => 'Color', 'type' => 'text', 'required' => false],
    ['key' => 'model', 'label' => 'Model', 'source' => 'equipment.model'],
    ['key' => 'length', 'label' => 'Length', 'type' => 'text', 'required' => false],
    ['key' => 'weight', 'label' => 'Total weight', 'type' => 'text', 'required' => false],
    ['key' => 'odometer', 'label' => 'Odometer', 'type' => 'text', 'required' => false],
    ['key' => 'capacity', 'label' => 'Motor capacity', 'type' => 'text', 'required' => false],
    ['key' => 'origin', 'label' => 'Origin country', 'type' => 'text', 'required' => false],
    ['key' => 'year', 'label' => 'Date of Production', 'source' => 'equipment.year_built'],
];
return [
    'code' => 'F-7.5.1-14', 'title' => 'INSPECTION REPORT FOR MOTORCYCLE', 'doc_ref' => 'F-7.5.1-14',
    'revision' => '03', 'revision_date' => '09/Feb./23', 'layout' => 'mo', 'inspector_label' => 'Inspector Name:',
    'default_statement' => '',
    'fields' => array_merge($head, $items),
    'notes' => '',
];