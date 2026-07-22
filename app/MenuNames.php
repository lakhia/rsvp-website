<?php

class MenuNames
{
    // Canonicalize a single menu item name.
    public static function canonicalize(string $name): string
    {
        $name = ucwords(trim($name));

        $replacements = [
            'Achaari'    => 'Achari',
            'Began'      => 'Baigan',
            'Begun'      => 'Baigan',
            'Bhaajiya'   => 'Bhajya',
            'Bhaji'      => 'Bhaaji',
            'Bhajji'     => 'Bhaaji',
            'Bhinda'     => 'Bhindi',
            'Chaval'     => 'Chawal',
            'Chawaal'    => 'Chawal',
            'Chickoli'   => 'Chikoli',
            'Chilly'     => 'Chilli',
            'Dal'        => 'Daal',
            'Doodi'      => 'Dudi',
            'Dudhi'      => 'Dudi',
            'Enchilladas'=> 'Enchiladas',
            'Guvar'      => 'Guvaar',
            'Guwar'      => 'Guvaar',
            'Kadahi'     => 'Karahi',
            'Kheema'     => 'Keema',
            'Khichro'    => 'Khichdo',
            'Malwi'      => 'Malvi',
            'Mathoo'     => 'Matho',
            'Mattar'     => 'Matar',
            'Mattur'     => 'Matar',
            'Mithas'     => 'Mithaas',
            'Mong'       => 'Moong',
            'Mutar'      => 'Matar',
            'Niyaaz'     => 'Niyaz',
            'Paaya'      => 'Paya',
            'Pau '       => 'Pav ',
            'Halvo'      => 'Halwo',
            'Halwa'      => 'Halwo',
            'Paledo'     => 'Palidu',
            'Paleedo'    => 'Palidu',
            'Palido'     => 'Palidu',
            'Patrela'    => 'Patra',
            'Payaa'      => 'Paya',
            'Pulav'      => 'Pulao',
            'Sandwiches' => 'Sandwich',
            'Seekh'      => 'Seek',
            'Suji'       => 'Sooji',
            'Urs'        => 'Urus',
            'Vegetables' => 'Veg',
            'Vegetable'  => 'Veg',
            'Rigna'      => 'Ringna',
            'Sodanu'     => 'Sodannu',
            ' With '     => ' w/ ',
            ' W/ '       => ' w/ ',
        ];

        foreach ($replacements as $from => $to) {
            $name = str_replace($from, $to, $name);
        }

        $whole_word = [
            'Kitchdi'  => 'Khitchdi',
            'Khitchri' => 'Khitchdi',
            'Kitchri'  => 'Khitchdi',
            'Khichri'  => 'Khitchdi',
            'Khichdi'  => 'Khitchdi',
            'Kadi'     => 'Kadhi',
            'Khadhi'   => 'Kadhi',
            'Kari'     => 'Kaari',
            'Khichdoo' => 'Khitchdo',
            'Khitchro' => 'Khitchdo',
            'Nan'      => 'Naan',
        ];

        return $whole_word[$name] ?? $name;
    }

}
