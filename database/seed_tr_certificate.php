<?php
// F-7.5.1-12 Trailer & Semi-Trailer report as a certificate template: header fields + one Yes / No / N/A select per form row.
// 'section' / 'item' keep the bilingual form text used by documents/certificate_tr.php.
$items = array (
  0 => 
  array (
    'key' => 'i01',
    'label' => '4.2 None of the outward-opening windows or ventilators an…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4- DIMENSIONS',
    'item' => '## 4- الابعاد
4.2 يجب ألا تبرز أي من النوافذ المفتوحة للخارج أو أجهزة التهوية والمرايا الجانبية بأكثر من 300 مم إلى الخارج من أقصى نقطة خارجية على كلا الجانبين
* في حالة وجود مرايا خلفية مثبتة بجرار يسحب مقطورة يزيد عرضها عن عرض الجرار ، قد يُسمح ببروز هذه المرايا التي لا تتجاوز 300 مم لتشكل أقصى نقطة خارجية على جانبي المقطورة.
#> DIMENSIONS 4-
4.2 None of the outward-opening windows or ventilators and wing mirrors shall protrude by more than 300 mm outward from the outermost point on both sides
* In the case of rear-wing mirrors attached to a tractor towing a trailer with a width exceeding that of the tractor, a protrusion of these mirrors not exceeding 300 mm form the outer most point on both sides of the trailer may be allowed.',
  ),
  1 => 
  array (
    'key' => 'i02',
    'label' => '4.3 The vehicle length shall be the distance between two…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4- DIMENSIONS',
    'item' => '4.3 يجب أن يكون طول السيارة هو المسافة بين مستويين عموديتين متعامدين مع المستوى المتوسط ​​الطولي وملامسة مقدمة السيارة ومؤخرتها على التوالي ولا تزيد عن 20 مترًا للشاحنات والمقطورات ، و 23 مترًا للجرارات وشبه المقطورات ، 25.25 م للمركبات بمقطورات مزدوجه.
4.3 The vehicle length shall be the distance between two vertical planes perpendicular to the longitudinal median plane and touching the front and rear of the vehicle respectively and it shall not exceed 20m for trucks and trailers, 23m for tractor and semi-trailers, 25.25m for Double Trailer Truck.',
  ),
  2 => 
  array (
    'key' => 'i03',
    'label' => '4.4 The vehicle width shall be the distance between two p…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4- DIMENSIONS',
    'item' => '4.4 يجب ان تكون عرض المركبة هي المسافة بين المستويين الرئيسين المتعامدين على المستوى المتوسط الافقي وتلامس من كلا الجانبين المستوى الجانبي ويجب الا تزيد عن 2.6 م
4.4 The vehicle width shall be the distance between two planes parallel to the longitudinal median plane and touching the vehicle on either side of the said plane and it shall not exceed 2.6m.',
  ),
  3 => 
  array (
    'key' => 'i04',
    'label' => '4.5 The height of the vehicle shall be the distance betwe…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '4- DIMENSIONS',
    'item' => '4.5 يجب ان تكون ارتفاع المركبة هي المسافة بين أسطح الدعم و المستوى الافقي ملامس اعلى جزء من المركبة و يجب ان لا تزيد عن 4.5 م
4.5 The height of the vehicle shall be the distance between the supporting surfaces and the horizontal plane touching the topmost part of the vehicle and it shall not exceed 4.5 m.',
  ),
  4 => 
  array (
    'key' => 'i05',
    'label' => '5.1 Any part other than the earth-touching parts of a mot…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5- MINIMUM ROAD CLEARANCE',
    'item' => '5- الحد الادنى لخلوص الطريق
5.1 يجب أن يكون أي جزء غير الأجزاء الملامسة للأرض في السيارة ذات خلوص كافٍ فوق سطح الأرض لضمان القيادة الآمنة
## 5- MINIMUM ROAD CLEARANCE
5.1 Any part other than the earth-touching parts of a motor vehicle shall have adequate clearance above the ground so as to ensure safe driving.',
  ),
  5 => 
  array (
    'key' => 'i06',
    'label' => '5.2 The clearance shall be such that any rigid parts of t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5- MINIMUM ROAD CLEARANCE',
    'item' => '5.2 يجب أن يكون الخلوص بحيث لا يجب أن تلمس أي أجزاء صلبة من السيارة مستوى الأرض وتتلف بسبب الاصطدام
5.2 The clearance shall be such that any rigid parts of the vehicle should not touch the level ground and get damaged due to impact.',
  ),
  6 => 
  array (
    'key' => 'i07',
    'label' => '5.3 Due to design constriction any projecting parts shoul…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '5- MINIMUM ROAD CLEARANCE',
    'item' => '5.3 طبقا لقيود التصميم فيجب ان تأمن الاجزاء البارزة لتجنب تأثير الاجزاء الصلبة لأسباب السلامة
5.3 Due to design constriction any projecting parts should be provided with guards to avoid the rigid part being affected for safety reasons.',
  ),
  7 => 
  array (
    'key' => 'i08',
    'label' => '6.1 The Gross Vehicle Weight shall be less than or equal…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '6- GROSS VEHICLE AND AXLE WEIGHT',
    'item' => '6- الوزن المحوري والاجمالي للمركبة
6.1 يجب أن يكون الوزن الإجمالي للمركبة أقل من أو يساوي مجموع سعة المحاور القصوى. يجب ألا يتجاوز الوزن الإجمالي للشاحنه" الباص" شاحنه ومقطوره" الجرار مع نصف المقطوره" عن 45 طنًا وبالنسبه للشاحنه بمقطوره مزدوجه عن 60 طنا
## 6- GROSS VEHICLE AND AXLE WEIGHT
6.1 The Gross Vehicle Weight shall be less than or equal to the sum of the maximum axles capacity. The gross vehicle weight for truck, bus, truck with trailer, tractor with semi – trailer shall not exceed 45 tones and for the double trailer truck shall not exceed (60) tons',
  ),
  8 => 
  array (
    'key' => 'i09',
    'label' => '6.5 Front supports of semi-trailers (standing legs) and l…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '6- GROSS VEHICLE AND AXLE WEIGHT',
    'item' => '6.5 يجب أن تكون الدعامات الأمامية لشبه المقطورات (أرجل التحميل) وتروس الرفع قادرة على دعم شبه المقطورات المحملة بالكامل بالإضافة إلى 10٪ زيادة عن الحمل.
6.5 Front supports of semi-trailers (standing legs) and lifting gears shall be capable of supporting the semi-trailers fully loaded plus 10% in excess of load.',
  ),
  9 => 
  array (
    'key' => 'i10',
    'label' => '6.7.1 Maximum axle (s) capacity of a vehicle is the permi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '6- GROSS VEHICLE AND AXLE WEIGHT',
    'item' => '6.7.1 الحد الأقصى لسعة المحور (المحاور) للمركبة هو الوزن المسموح به المقابل للوزن الأقصى الذي يجب أن يحمله المحور (المحاور) كما هو محدد من قبل الشركة المصنعة للمركبة، ولا يتجاوز مواصفات الشركة المصنعة للمحور. يجب أن تكون السعة القصوى للمحور (المحاور) أقل من أو تساوي مجموع السعات القصوى للإطارات
6.7.1 Maximum axle (s) capacity of a vehicle is the permissible weight corresponding to the maximum weight to be carried by the axle (s) as defined by the vehicle manufacturer, not exceeding the axle manufacturer’s specifications. The maximum axle(s) capacity shall be less than or equal to the sum of the maximum capacities of the tires',
  ),
  10 => 
  array (
    'key' => 'i11',
    'label' => '7.1 The trailer should not pull to left or right during d…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7- STABILITY',
    'item' => '7- الاتزان
7.1 يجب ألا تنحرف المقطورة إلى اليسار أو اليمين أثناء القيادة
## 7- STABILITY
7.1 The trailer should not pull to left or right during driving',
  ),
  11 => 
  array (
    'key' => 'i12',
    'label' => '7.3.1 Any motor vehicle and any semi-trailer must be able…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '7.3 MANEUVERABILITY REQUIREMENTS',
    'item' => '7.3 متطلبات القدرة على المناورة
7.3.1 يجب أن تكون أي مركبة أو نصف مقطورة قادرة على المناورة على كلا الجانبين للحصول على مسار دائري كامل بزاوية 360 درجة داخل منطقة محددة بدائرتين متحدة المركز ، والدائرة الخارجية نصف قطرها 12.50 م والدائرة الداخلية نصف قطرها 5.30 م.
## 7.3 MANEUVERABILITY REQUIREMENTS
7.3.1 Any motor vehicle and any semi-trailer must be able to manoeuvre on either side for a complete circular trajectory of 360° inside an area defined by two concentric circles, the outer circle having a radius of 12.50 m and the inner circle having a radius of 5.30 m.',
  ),
  12 => 
  array (
    'key' => 'i13',
    'label' => '8.2 The resistance between all exposed conductive parts a…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8. ELECTRIC SYSTEM',
    'item' => '8. النظام الكهربائي
8.2 يجب أن تكون المقاومة بين جميع الأجزاء الموصلة المكشوفة والهيكل الكهربائي أقل من 0.1 أوم عندما يكون هناك تدفق تيار لا يقل عن 0.2 أمبير في حالة المركبات الهجينة.
## 8. ELECTRIC SYSTEM
8.2 The resistance between all exposed conductive parts and the electrical chassis shall be lower than 0.1ohms when there is current flow of at least 0.2amps in the case of hybrid vehicles.',
  ),
  13 => 
  array (
    'key' => 'i14',
    'label' => '8.5 Electrical parts should be provided with a protection…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8. ELECTRIC SYSTEM',
    'item' => '8.5 يجب تزويد الأجزاء الكهربائية بغلاف حماية ضد التأثيرات الخارجية و / أو الأجسام الغريبة و / أو الماء.
8.5 Electrical parts should be provided with a protection enclosure against access external influences, foreign objects and/or water.',
  ),
  14 => 
  array (
    'key' => 'i15',
    'label' => '8.7 The battery terminals shall be electrically insulated…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8. ELECTRIC SYSTEM',
    'item' => '8.7 يجب أن تكون أطراف البطارية معزولة كهربائياً أو مغطاة بغطاء صندوق البطارية العازل. يجب أن توضع في صندوق لة فتحات تهوية .
8.7 The battery terminals shall be electrically insulated or covered by the insulating battery box cover. They shall be fitted in a vented box.',
  ),
  15 => 
  array (
    'key' => 'i16',
    'label' => '8.9 A switch for breaking the electrical circuits shall b…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '8. ELECTRIC SYSTEM',
    'item' => '8.9 يجب وضع مفتاح لغلق الدوائر الكهربائية في أقرب مكان ممكن من البطارية.
8.9 A switch for breaking the electrical circuits shall be placed as close to the battery as practicable.',
  ),
  16 => 
  array (
    'key' => 'i17',
    'label' => '9.1 The braking system provided to the trailer shall be s…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9- BRAKING SYSTEM',
    'item' => '9- نظام الفرامل
9.1 يجب أن يكون نظام الفرامل المقدم للمقطورة مناسبًا للمركبة ويضمن أداء فرملة آمنًا في الظروف العادية والطارئة.
## 9- BRAKING SYSTEM
9.1 The braking system provided to the trailer shall be suitable to the vehicle concern and ensure safe braking performance under normal and emergency conditions.',
  ),
  17 => 
  array (
    'key' => 'i18',
    'label' => '9.4 No material affecting the public health, directly or…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9- BRAKING SYSTEM',
    'item' => '9.4 يجب عدم استخدام أي مواد تؤثر على الصحة العامة ، مباشرة أو بعد التفاعل مع مكونات أخرى مثل الأسبست والكادميوم ، في نظام الفرامل.
9.4 No material affecting the public health, directly or after reaction with other components such as asbestos and cadmium, shall be used in the brake system.',
  ),
  18 => 
  array (
    'key' => 'i19',
    'label' => '9.6 Brake pipes shall be so attached to the chassis or eq…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9- BRAKING SYSTEM',
    'item' => '9.6 يجب أن تكون مواسير الفرامل ملحقة بالهيكل أو ما يعادله بحيث لا تتعرض للتلف دون داع من خلال الاهتزاز أو التآكل ويجب أن تكون مصنوعة من مواد مقاومة للتآكل أو معالجتها بأي عملية تضمن هذه المقاومة.
9.6 Brake pipes shall be so attached to the chassis or equivalent that they are not unnecessarily subjected to damage through vibration or abrasion and shall be made of materials resistant to corrosion or treated by any process which ensures such resistance.',
  ),
  19 => 
  array (
    'key' => 'i20',
    'label' => '9.7 The parking braking system shall make it possible to…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9- BRAKING SYSTEM',
    'item' => '9.7 يجب أن يسمح نظام فرملة الانتظار بإبقاء السيارة ثابتة عند التدرج لأعلى أو لأسفل بنسبة 18٪ حتى في غياب السائق ، ثم يتم تثبيت أجزاء العمل في وضع مغلق بواسطة جهاز ميكانيكي بحت. في حالة الشاحنات ذات المقطورات ، سيكون التدرج 12٪.
9.7 The parking braking system shall make it possible to hold the vehicle stationary on an up or down gradient of 18% even in the absence of the driver, the working parts being then held in locked position by a purely mechanical device. In the case of vehicles with trailers, the gradient will be 12%.',
  ),
  20 => 
  array (
    'key' => 'i21',
    'label' => '9.8 The trailers category O3, O4 shall be provided with A…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '9- BRAKING SYSTEM',
    'item' => '9.8 يجب أن تزود المقطورات من فئة O3 و O4 بنظام المكابح المانعة للانغلاق (ABS).
9.8 The trailers category O3, O4 shall be provided with Anti-lock braking system (ABS).',
  ),
  21 => 
  array (
    'key' => 'i22',
    'label' => '10.2 Every Trailer vehicle shall be equipped with springs…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '10- SUSPENSION SYSTEM',
    'item' => '## 10-نظام التعليق
10.2يجب أن تكون كل مقطورة آلية مزودة بنوابض (يايات) وامتصاص الصدمات (مساعدات) أو أي نظام آخر لامتصاص الصدمات بسعة كافية حسب الوزن الإجمالي للمركبة، ضد التأثير من الأرض ويمكن أن يضمن القيادة الآمنة على الطريق غير المستوي والخشن
## 10- SUSPENSION SYSTEM
10.2 Every Trailer vehicle shall be equipped with springs and shock absorber or any other shock absorbing system which has adequate capacity depending on the GVW of the vehicle, against the impact from the ground and can ensure safe driving on uneven and rough road',
  ),
  22 => 
  array (
    'key' => 'i23',
    'label' => '10.3 The suspension system provided for trailer used in c…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '10- SUSPENSION SYSTEM',
    'item' => '10.3يجب أن يكون نظام التعليق المقدم للمقطورة المستخدمة في مواقع البناء مناسبًا للحمل الأقصى المطبق والتضاريس الوعرة التي تستخدم فيها.
10.3 The suspension system provided for trailer used in construction sites shall be suitable to carry the maximum load applied and the rough terrain in which they are used.',
  ),
  23 => 
  array (
    'key' => 'i24',
    'label' => '10.4The durability of the suspension system should be tes…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '10- SUSPENSION SYSTEM',
    'item' => '10.4 يجب اختبار متانة نظام التعليق عن طريق إجراء اختبارات المتانة وفقًا للمواصفات الدولية ومواصفات الشركة المصنعة ذات الصلة.
10.4The durability of the suspension system should be tested by carrying out durability tests in accordance with the relevant international and manufacturer\'s specifications.',
  ),
  24 => 
  array (
    'key' => 'i25',
    'label' => '11.1 The frame and body shall be so secure and strong eno…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '## 11- الهيكل والجسم
11.1 يجب أن يكون الهيكل والجسم آمنين وقويين بما يكفي لتحمل تشغيل المقطورة بشكل كامل
## 11- FRAME AND BODY
11.1 The frame and body shall be so secure and strong enough as to fully withstand the operation of the Trailer',
  ),
  25 => 
  array (
    'key' => 'i26',
    'label' => '11.2 The chasse shall be strong enough to carry the maxim…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '11.2 يجب أن تكون الهيكل قوي بما يكفي لتحمل الحمولة القصوى المطبقة على المقطورة قيد الاستخدام
11.2 The chasse shall be strong enough to carry the maximum load applied to the Trailer in use.',
  ),
  26 => 
  array (
    'key' => 'i27',
    'label' => '11.5 Trailers shall be mounted with rear and side shields…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '11.5 يجب تركيب حواجز خلفية وجانبية (حماية خلفية وجانبية) للمقطورات و ذلك للحماية ضد وصول الحادث تحت العجلات اثناء تحرك المركبة في حالة الاصطدام الخلفي أو الجانبي
11.5 Trailers shall be mounted with rear and side shields (rear and side under- run protections) to protect against under running of vehicles in the event of rear or side collision.',
  ),
  27 => 
  array (
    'key' => 'i28',
    'label' => '11.6 All vehicles carrying goods, including tankers, mobi…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '11.6 جميع المركبات التي تحمل البضائع و تشمل من صهاريج ورافعات متحركة وورش متنقلة ومقطورات وأنصاف المقطورات التي تزيد كتلتها عن 3.5 طن يجب أن تكون مزودة بحاجز حماية خلفي و ذلك لحماية المركبات من تحت الكفرات في حالة الاصطدام الخلفي
11.6 All vehicles carrying goods, including tankers, mobile cranes, mobile workshops, trailers and semi-trailers of maximum mass exceeding 3.5 tones shall be equipped with rear under run protective devices to protect against under running of vehicles in the event of rear collision',
  ),
  28 => 
  array (
    'key' => 'i29',
    'label' => '11.7 Lateral protection fix to all vehicles carrying good…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '11.7 يجب نثبيت الحاجز الجانبي لكل المركبات التي تحمل البضائع و تشمل من صهاريج ورافعات متحركة وورش متنقلة ومقطورات وأنصاف المقطورات التي تزيد كتلتها عن 3.5 طن الفعال على طول كلا الجانبين لتوفير حماية فعالة لمستخدمي الطريق غير المحميين ضد خطر السقوط تحت جوانب السيارة والوقوع تحت العجلات.
11.7 Lateral protection fix to all vehicles carrying goods, including tankers mobile cranes, mobile workshops, trailers and semi-trailers of maximum mass exceeding 3.5 tones shall be constructed and equipped in such a way as to offer, throughout their length, at both sides effective protection to unprotected road users against the risk of falling under the sides of the Vehicle and being caught under the wheels.',
  ),
  29 => 
  array (
    'key' => 'i30',
    'label' => '11.8 Spray-suppression devices must be constructed in suc…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '11.8 يجب نثبيت أجهزة منع الرذاذ (غطاء الكفرات) بطريقة تجعلها تعمل بشكل صحيح عند استخدامها بشكل طبيعي على الطرق المبتلة. علاوة على ذلك ، يجب عليهم تصنيع مقطورات السيارات لا تتضمن أي عيب هيكلي أو عيب يضر بوظائفها أو سلوكها السليم.
11.8 Spray-suppression devices must be constructed in such a way that they operate properly when used normally on wet roads. Moreover, the automotive trailer manufacturing they must incorporate no structural or defect detrimental to their proper functioning or behavior.',
  ),
  30 => 
  array (
    'key' => 'i31',
    'label' => '11.11 The rear of the trailers shall be provided with des…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '11- FRAME AND BODY',
    'item' => '11.11 يجب تزويد الجزء الخلفي من المقطورات بموقع محدد يكفي في الحجم لتثبيت لوحات الأرقام المصنوعة وفقًا للائحة الفنية السعودية رقم SASO 572
11.11 The rear of the trailers shall be provided with designated location sufficient in size to fix the number plates made in accordance with the SASO technical regulation No. SASO 572.',
  ),
  31 => 
  array (
    'key' => 'i32',
    'label' => '12.1 When two or more vehicles are operated in combinatio…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '12- COUPLING DEVICES AND TOWING METHODS',
    'item' => '## 12- اجهزة التوصيل وطرق الجر
12.1 عند تشغيل مركبتين أو أكثر بشكل جماعي، يجب تصميم وتصنيع وتركيب أدوات الجر التي تصل المركبات بعضها ببعض، وأيضا يجب تصميم وتصنيع المركبات بحيث لا تنحرف المركبات المقطورة – عندما يتم تشغيل مجموعة المركبات في خط مستقيم على سطح مستوٍ وأملس ومُمهد – عن مسارها بمسافة تزيد عن ٧٦مم في أي من جانبي مسار المركبة القاطرة.
## 12- COUPLING DEVICES AND TOWING METHODS
12.1 When two or more vehicles are operated in combination, the coupling devices connecting the vehicles shall be designed, constructed and installed, and the vehicles shall be designed and constructed so that when the combination is operated in a straight line on a level, smooth, paved surface, the path of the towed vehicle will not deviate more than 76 mm to either side of the path of the towing vehicle.',
  ),
  32 => 
  array (
    'key' => 'i33',
    'label' => '12.3 The manufacturer must lay down the dimensions necess…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '12- COUPLING DEVICES AND TOWING METHODS',
    'item' => '12.3 يجب على الشركة الصانعة تحديد الابعاد اللازمة لجهاز الجر الميكانيكي المتوافق بين المقطورة او الكرفان و الشاحنة الجرار و لك في حالة استخدام جهاز الجر الكروي
12.3 The manufacturer must lay down the dimensions necessary for the compatibility of mechanical coupling devices between light trailers or caravans and towing vehicles, when the latter are fitted with a coupling ball.',
  ),
  33 => 
  array (
    'key' => 'i34',
    'label' => '12.5 Every fifth wheel assembly shall have a locking mech…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '12- COUPLING DEVICES AND TOWING METHODS',
    'item' => '12.5 يجب ان يكون لنظام العجلة الخامسة نظام غلق.
12.5 Every fifth wheel assembly shall have a locking mechanism',
  ),
  34 => 
  array (
    'key' => 'i35',
    'label' => '13.2 The tires shall withstand endurance test, high speed…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '13- WHEELS (TYRES AND RIMS)',
    'item' => '## 13- العجلات (الاطارات و الجنوط)
13.2 يجب أن تتحمل الإطارات اختبار التحمل ، واختبار الأداء للسرعات العالية ، واختبارات تثبيت الجنط مع الاطار ، واختبارات المتانة، واختبارات مقاومة درجات الحرارة ، ومقاومة التدحرج واختبارات الانزلاق الرطب و المحددة في ECE 117.
## 13- WHEELS (TYRES AND RIMS)
13.2 The tires shall withstand endurance test, high speed performance test, Bead unseating tests, Strength tests, Temperature resistance tests, Rolling resistance and wet grip tests specified in ECE 117.',
  ),
  35 => 
  array (
    'key' => 'i36',
    'label' => '13.3 Retreaded tires shall display on both sidewalls in t…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '13- WHEELS (TYRES AND RIMS)',
    'item' => '13.3 في حالة استخدام كفرات معاد تصنيعها يجب توضيح لك على كلا الجانبين في حالة الكفرات المتماثلة البيانات و توضيح ذلك على الجانب الخارجي على الاقل في حالة بيانات الكفر غير متماثلة.
13.3 Retreaded tires shall display on both sidewalls in the case of symmetrical tires and at least on the outer sidewall in the case of asymmetrical tires.',
  ),
  36 => 
  array (
    'key' => 'i37',
    'label' => '13.4 The retreaded pneumatic tire accepted for trailer ca…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '13- WHEELS (TYRES AND RIMS)',
    'item' => '13.4 الاطارات المجددة مقبولة لفئة المقطوراتO3, O4.
13.4 The retreaded pneumatic tire accepted for trailer category O3, O4.',
  ),
  37 => 
  array (
    'key' => 'i38',
    'label' => '13.5 The trailers shall be provided with spare tire.',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '13- WHEELS (TYRES AND RIMS)',
    'item' => '13.5 يجب ان تزود المقطورات بإطار احتياطي.
13.5 The trailers shall be provided with spare tire.',
  ),
  38 => 
  array (
    'key' => 'i39',
    'label' => '14.1.2 The lighting equipment shall be so fitted that und…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '## 14- معدات الإضاءة
14.1.2 يجب أن تكون معدات الإضاءة مُجهزة بحيث تكون مُرضية في ظل ظروف الاستخدام العادية وبغض النظر عن أي اهتزازات قد تتعرض لها في مثل هذا الاستخدام.
## 14- LIGHTING EQUIPMENT
14.1.2 The lighting equipment shall be so fitted that under normal conditions of use and notwithstanding any vibrations to which they may be subjected in such use their satisfactory.',
  ),
  39 => 
  array (
    'key' => 'i40',
    'label' => '14.1.3 Lighting equipment mounted in pairs shall be of th…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.1.3 يجب أن تكون معدات الإضاءة المركبة في أزواج (على الجانبين) من نفس المتطلبات اللونية والخصائص الضوئية المناسبة بشكل متماثل.
14.1.3 Lighting equipment mounted in pairs shall be of the same colorimetric requirements and symmetrically appropriate photometric characteristics.',
  ),
  40 => 
  array (
    'key' => 'i41',
    'label' => '14.2.1 Every Trailer shall be equipped with a stop lamp (…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.1 تجهز كل مقطورة بلمبة توقف (لمبة فرامل) باللون الاحمر في كل جانب من الخلف.
◄ تعمل أضواء التوقف عند استخدام فرامل خدمة السيارة أو عند استخدام خاصية الفرامل
14.2.1 Every Trailer shall be equipped with a stop lamp (brake lamp) with display a red colour on each side at the rear.
► Stop lamps shall be actuated upon application of the service brake',
  ),
  41 => 
  array (
    'key' => 'i42',
    'label' => '14.2.2 Every motor vehicle shall be equipped with at leas…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.2 تجهز كل مركبة بلمبة إضاءة خلفي واحد على الأقل على كل جانب في الخلف و يجب ان تظهر مضاءة باللون الأحمر
◄ يجب أن تكون موضع الاضاءة الخلفية ذات شدة اضاءة بحيث تكون مرئية بوضوح في الليل على مسافة مناسبة إلى الخلف لتجنب أي تأثير خلفي
◄ يجب أن تكون الاضاءة الخلفية متصلة معا بحيث تضيء في نفس الوقت على كلا الجانبين مع وضع الاضاءة الأمامي
14.2.2 Every motor vehicle shall be equipped with at least one rear position lamp on each side, at the rear and shall display a red colour
► The intensity of rear position lamps shall be such that it will be clearly visible at night at a suitable distance to the rear to avoid any rear impact
► Rear position lamps shall be wired so that it will light simultaneously on both sides with the front position lamps',
  ),
  42 => 
  array (
    'key' => 'i43',
    'label' => '14.2.3 Every Trailer shall be equipped with not more than…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.3 يجب أن تزود كل مقطورة بما لا يزيد عن لمبتين للتحرك للخلف.
14.2.3 Every Trailer shall be equipped with not more than two reversing lamps.',
  ),
  43 => 
  array (
    'key' => 'i44',
    'label' => '14.2.4 Every Trailer shall be equipped with direction ind…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.4 يجب تجهيز كل مقطورة بأضاءات لتحديد الاتجاه (الحركة لليمين او لليسار) ، بحيث تكون اثنان مثبتان في الخلف .
◄ يجب أن تكون شدة مصابيح مؤشرات الاتجاه مرئية من أي ارتفاع لا يزيد عن 2.5 متر فوق سطح الأرض على مسافة آمنة إلى الخلف.
◄ يجب أن تكون مصابيح مؤشر الاتجاه الأمامي والخلفي مرئية ، أثناء التشغيل ، حتى في وضح النهار ، وتكون باللون الكهرماني(الاصفر) / الأحمر
14.2.4 Every Trailer shall be equipped with direction indicator lamps, two mounted on the rear
► The intensity of the direction indicator lamps shall be such that it will be visible from any height not more than 2.5 meters above the ground at a safe distance to the rear.
► Front and rear direction indicator lamps shall, when in operation, be visible, even in daytime, and shall be amber/red in colour',
  ),
  44 => 
  array (
    'key' => 'i45',
    'label' => '14.2.5 Every Trailer shall be equipped with hazard warnin…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.5 تجهز كل مقطورة بأضاءة تومض للتحذير من المخاطر بحيث اثنان مثبتان في الخلف . يتم إعطاء الإشارة من خلال التشغيل المتزامن لأضاءه تحديد الاتجاه
◄ يجب أن تتوافق لمبات وميض التحذير من المخاطر مع متطلبات لمبات مؤشرات الاتجاه.
14.2.5 Every Trailer shall be equipped with hazard warning flashing lamps, two mounted on the rear. The signal shall be given by simultaneous operation of the direction indicator lamps
► Hazard warning flashing lamps shall comply with the requirements of direction indicator lamps.',
  ),
  45 => 
  array (
    'key' => 'i46',
    'label' => '14.2.6 Trailers, semi-trailers if width exceeding 2.1 m s…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.6 يتم تجهيز المقطورات وشبه المقطورات التي يزيد عرضها عن 2.1 متر أضاءه خلوص ، على الأقل واحدة على كل جانب ، في الأمام والخلف
◄ يجب تثبيت أضاءه الخلوص لتحديد حدود العرض الأقصى للمركبة.
◄ يجب أن تعرض أضاءه الخلوص الأمامي اللون الكهرماني أو الأبيض، والخلفي باللون الكهرماني (الاصفر) أو الأحمر
14.2.6 Trailers, semi-trailers if width exceeding 2.1 m shall be equipped with clearance lamps, with at least one on each side, at the front and at the rear
► The clearance lamp shall be mounted so as to indicate the extreme width of the Trailer vehicle.
► Front clearance lamps shall display an amber or white colour and the rear shall be amber or red colour.',
  ),
  46 => 
  array (
    'key' => 'i47',
    'label' => '14.2.7 Any motor vehicle exceeding 6m in length shall be…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.7 يجب ان تجهز أي مركبة يزيد طولها عن 6 أمتار بمصابيح إشارة أمامية وخلفية أو عاكسات جانبية على جانبي المقطورات.
◄ يجب أن تكون مصابيح العلامة الجانبية مرئية بوضوح ليلاً على مسافة مناسبة من جانب السيارة لتجنب أي حوادث.
◄ يتم عرض الاضاءة باللون الكهرماني (الاصفر) للمبات المثبتة في الأمام والوسط، والأحمر أو باللون الكهرماني (الاصفر) لمن هم في الخلف.
14.2.7 Any motor vehicle exceeding 6m in length shall be equipped with front and rear side marker lamps or side reflectors on both sides of trailer vehicles
► The side marker lamps shall be clearly visible at night at a suitable distance from the side of the vehicle to avoid any accidents.
► Shall display amber colour for those equipped on the front and the centre, and red or amber colour for those on the rear.',
  ),
  47 => 
  array (
    'key' => 'i48',
    'label' => '14.2.7.1 The side reflectors shall be clearly visible at…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.7.1 يجب أن تكون العواكس الجانبية مرئية بوضوح ليلاً على مسافة كافية من جانب السيارة لتجنب أي حادث عندما يضيء بتأثير أشعة اضاءة الكشافات الامامية
◄ يجب أن يعكس اللون الكهرماني (الاصفر) للعاكسات الأمامية والوسطى ، واللون الأحمر أو الكهرماني (الاصفر) للجزء الخلفي
14.2.7.1 The side reflectors shall be clearly visible at night at a sufficient distance from the side of the vehicle to avoid any accident when illuminated by head lamp beams
► Shall reflect amber colour for the front and centre side reflectors, and red or amber colour for the rear',
  ),
  48 => 
  array (
    'key' => 'i49',
    'label' => '14.2.8 Every motor vehicle shall be equipped with a regis…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.8 يجب أن تزود كل مركبة بإضاءة لتضيء لوحة الارقام على ان يكون ضوء أبيض لتوضيح لوحة الأرقام لخلفية ، مما يجعلها مرئية بوضوح من مسافة 20 مترًا إلى الخلف
◄ يجب أن يكون أضاءه لوحة الارقام متصلة مع قيد التشغيل لإضاءة الكشافات الامامية وايضا اضاءة الوضع الأمامية والخلفية
14.2.8 Every motor vehicle shall be equipped with a registration plate lamp of such construction as to illuminate, with a white light, the rear registration number plate, making it clearly visible from a distance of 20 meters to the rear
► The registration plate lamp shall be wired so that it is on when the head lamps and front and rear position lamps are on',
  ),
  49 => 
  array (
    'key' => 'i50',
    'label' => '14.2.9 The rear fog lamp is optional provided if the inte…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '14- LIGHTING EQUIPMENT',
    'item' => '14.2.9 لمبة الضباب الخلفي اختياري إذا كانت شدة المصابيح الخلفية تتوافق مع اللائحة الفنية السعودية
◄ يجب أن يكون واحد مصباح ضباب خلفي على الأقل وأن يظهر باللون الأحمر
14.2.9 The rear fog lamp is optional provided if the intensity of the rear lamps complies with the
SASO regulation.
► the rear fog lamp should be a minimum one in the rear and display red colour',
  ),
  50 => 
  array (
    'key' => 'i51',
    'label' => '16.1.4 Each VIN shall consist of seventeen (17) character…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '16 VEHICLE IDENTIFICATION NUMBER (VIN)',
    'item' => '## 16- الرقم المميز للمركبة (رقم الهيكل)
16.1.4 كل رقم شاص يتكون من 17 حرف
## 16 VEHICLE IDENTIFICATION NUMBER (VIN)
16.1.4 Each VIN shall consist of seventeen (17) characters.',
  ),
  51 => 
  array (
    'key' => 'i52',
    'label' => '16.1.9 The VIN location shall be readable from outside ac…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '16 VEHICLE IDENTIFICATION NUMBER (VIN)',
    'item' => '16.1.9 يجب ان يكون موقع رقم الشاص يسمح للقراءة من الخارج طبقا للمواصفة SASO 2251
16.1.9 The VIN location shall be readable from outside according to SASO 2251',
  ),
  52 => 
  array (
    'key' => 'i53',
    'label' => '16.1.10 Each vehicle must have its own special number eng…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '16 VEHICLE IDENTIFICATION NUMBER (VIN)',
    'item' => '16.1.10 يجب ان تكون لكل مركبة رقم الشاص الخاص بها و المحفور بالهيكل بالجانب الايسر كما تم تحديدة من قبل الصانع
16.1.10 Each vehicle must have its own special number engraved or engraved on the internal frame structure or on any other similar body, on the left side of the vehicle, as determined by the manufacturer.',
  ),
  53 => 
  array (
    'key' => 'i54',
    'label' => '17.2 The manufacturer shall affix, on the chassis of the…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17- COMPLIANCE PLATE',
    'item' => '## 17- بطاقة البيانات
17.2 يجب على الشركة المصنعة وضع ملصق على الشاص لكل مركبة أو على شاسيه المقطورة بحيث يفي بالمتطلبات التالية:
17.2.1 تصنع من مادة مقاومة للتلف
17.2.2 يجب أن يتم لصق الملصق بشكل دائم، ما لم يتم تثبيته ، بحيث لا يمكن إزالته دون إتلافه
#> 17- COMPLIANCE PLATE
17.2 The manufacturer shall affix, on the chassis of the trailer, a label meeting the following
>> requirements:
>> 17.2.1 It shall be made of material resistant to deterioration.
>> 17.2.2 The label shall, unless riveted, be permanently affixed in such a manner that it cannot be removed without being destroyed.',
  ),
  54 => 
  array (
    'key' => 'i55',
    'label' => '17.2.3 The following information shall be written (in Ara…',
    'type' => 'select',
    'options' => 
    array (
      0 => 'Yes',
      1 => 'No',
      2 => 'N/A',
    ),
    'required' => true,
    'section' => '17- COMPLIANCE PLATE',
    'item' => '17.2.3 تكتب المعلومات التالية (باللغة العربية أو باللغة الإنجليزية):
17.2.3 The following information shall be written (in Arabic or in English language):
17.2.3.1 اسم الصانع وبلد الصنع (أو التجميع)
17.2.3.1 Name of manufacturer and country of manufacture (or assemble)
17.2.3.2 سنة وشهر الإنتاج
17.2.3.2 Year and month of production
17.2.3.3 الوزن الأقصى (الإجمالي) للمركبة (GVW) بالكيلوجرام
17.2.3.3 Maximum (gross) vehicle weight (GVW) in kilograms
17.2.3.4 أقصى وزن (إجمالي) للمحور (GAW) لكل محور بالكيلوجرام
17.2.3.4 Maximum (gross) axle weight (GAW) for each axle, in kilograms
17.2.3.5 الرقم المميز للمقطوره
17.2.3.5 The trailer identification number
17.2.3.6 التصنيف للمقطوره
17.2.3.6 The category of trailer.
17.2.3.7 عبارة “هذه المركبة تتوافق مع جميع اللوائح الفنية السعودية السارية المفعول حتى تاريخ الصنع
17.2.3.7 The statement “This trailer conforms to all applicable SASO standard and Technical Regulations in effect up to the date of manufacture””.',
  ),
);

$head = [
    ['key' => 'cert_no', 'label' => 'Report No.', 'source' => 'cert.certificate_no'],
    ['key' => 'report_date', 'label' => 'Report Date', 'source' => 'cert.issue_date'],
    ['key' => 'center_name', 'label' => 'Inspection Center Name', 'type' => 'text', 'required' => false],
    ['key' => 'center_address', 'label' => 'Inspection Center Address', 'type' => 'text', 'required' => false],
    ['key' => 'client', 'label' => 'Client Name', 'source' => 'client.name'],
    ['key' => 'origin', 'label' => 'Origin Country', 'type' => 'text', 'required' => false],
    ['key' => 'eq_desc', 'label' => 'Product Description', 'source' => 'equipment.description'],
    ['key' => 'model', 'label' => 'Model', 'source' => 'equipment.model'],
    ['key' => 'type', 'label' => 'Type', 'type' => 'text', 'required' => false],
    ['key' => 'brand', 'label' => 'Brand / Trademark Name', 'source' => 'equipment.manufacturer'],
    ['key' => 'mfg_date', 'label' => 'Manufacture Date', 'source' => 'equipment.year_built'],
    ['key' => 'vin', 'label' => 'Chassis No. (VIN) - 17 characters', 'type' => 'text', 'required' => true],
    ['key' => 'dim_h', 'label' => 'Height (m)', 'type' => 'text', 'required' => false],
    ['key' => 'dim_w', 'label' => 'Width (m)', 'type' => 'text', 'required' => false],
    ['key' => 'dim_l', 'label' => 'Length (m)', 'type' => 'text', 'required' => false],
    ['key' => 'axle_1', 'label' => 'Max. Axle Weight - Axle 1 (kg)', 'type' => 'text', 'required' => false],
    ['key' => 'axle_2', 'label' => 'Max. Axle Weight - Axle 2 (kg)', 'type' => 'text', 'required' => false],
    ['key' => 'axle_3', 'label' => 'Max. Axle Weight - Axle 3 (kg)', 'type' => 'text', 'required' => false],
    ['key' => 'gross', 'label' => 'Gross Weight (kg)', 'type' => 'text', 'required' => false],
    ['key' => 'insp_date', 'label' => 'Inspection Date', 'source' => 'cert.inspection_date'],
];
return [
    'code' => 'F-7.5.1-12', 'title' => 'INSPECTION REPORT FOR TRAILER & SEMI-TRAILER', 'doc_ref' => 'F-7.5.1-12',
    'revision' => '03', 'revision_date' => '09/Feb./23', 'layout' => 'tr', 'inspector_label' => 'Inspector Name:',
    'default_statement' => '',
    'fields' => array_merge($head, $items),
    'notes' => '',
];