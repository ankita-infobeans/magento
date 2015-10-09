<?php

class Gorilla_ChasePaymentech_Helper_Data extends Mage_Paygate_Helper_Data
{
    /**
     * @return string
     */
    public function getCustomerAddCreditCardUrl()
    {
        return $this->_getUrl('chasepaymentech/account/add');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function convertIdToCode($id)
    {
        $states = array();
        $states[1] = 'AL';
        $states[2] = 'AK';
        $states[3] = 'AS';
        $states[4] = 'AZ';
        $states[5] = 'AR';

        $states[12] = 'CA';
        $states[13] = 'CO';
        $states[14] = 'CT';
        $states[15] = 'DE';
        $states[16] = 'DC';
        $states[17] = 'FM';
        $states[18] = 'FL';
        $states[19] = 'GA';
        $states[20] = 'GU';
        $states[21] = 'HI';
        $states[22] = 'ID';
        $states[23] = 'IL';
        $states[24] = 'IN';
        $states[25] = 'IA';
        $states[26] = 'KS';
        $states[27] = 'KY';
        $states[28] = 'LA';
        $states[29] = 'ME';
        $states[30] = 'MH';
        $states[31] = 'MD';
        $states[32] = 'MA';
        $states[33] = 'MI';
        $states[34] = 'MN';
        $states[35] = 'MS';
        $states[36] = 'MO';
        $states[37] = 'MT';
        $states[38] = 'NE';
        $states[39] = 'NV';
        $states[40] = 'NH';
        $states[41] = 'NJ';
        $states[42] = 'NM';
        $states[43] = 'NY';
        $states[44] = 'NC';
        $states[45] = 'ND';
        $states[46] = 'MP';
        $states[47] = 'OH';
        $states[48] = 'OK';
        $states[49] = 'OR';
        $states[50] = 'PW';
        $states[51] = 'PA';
        $states[52] = 'PR';
        $states[53] = 'RI';
        $states[54] = 'SC';
        $states[55] = 'SD';
        $states[56] = 'TN';
        $states[57] = 'TX';
        $states[58] = 'UT';
        $states[59] = 'VT';
        $states[60] = 'VI';
        $states[61] = 'VA';
        $states[62] = 'WA';
        $states[63] = 'WV';
        $states[64] = 'WI';
        $states[65] = 'WY';

        // Canadian Provinces
        // edited 12-5-07
        $states[66] = 'AB';
        $states[67] = 'BC';
        $states[68] = 'MB';
        $states[70] = 'NB';
        $states[69] = 'NL';

        $states[72] = 'NT';
        $states[71] = 'NS';
        $states[73] = 'NU';
        $states[74] = 'ON';
        $states[75] = 'PE';
        $states[76] = 'QC';
        $states[77] = 'SK';
        $states[78] = 'YT';

        return isset($states[$id]) ? $states[$id] : $id;
    }

    /**
     * @param $state
     * @internal param $state_name
     * @return mixed
     */
    public function convertStateNameToCode($state)
    {
        if (empty($state)) {
            return $state;
        }

        $states = array();
        $states['ALABAMA'] = 'AL';
        $states['ALASKA'] = 'AK';
        $states['AMERICAN SAMOA'] = 'AS';
        $states['ARIZONA'] = 'AZ';
        $states['ARKANSAS'] = 'AR';
        $states['CALIFORNIA'] = 'CA';
        $states['COLORADO'] = 'CO';
        $states['CONNECTICUT'] = 'CT';
        $states['DELAWARE'] = 'DE';
        $states['DISTRICT OF COLUMBIA'] = 'DC';
        $states['FEDERATED STATES OF MICRONESIA'] = 'FM';
        $states['FLORIDA'] = 'FL';
        $states['GEORGIA'] = 'GA';
        $states['GUAM'] = 'GU';
        $states['HAWAII'] = 'HI';
        $states['IDAHO'] = 'ID';
        $states['ILLINOIS'] = 'IL';
        $states['INDIANA'] = 'IN';
        $states['IOWA'] = 'IA';
        $states['KANSAS'] = 'KS';
        $states['KENTUCKY'] = 'KY';
        $states['LOUISIANA'] = 'LA';
        $states['MAINE'] = 'ME';
        $states['MARSHALL ISLANDS'] = 'MH';
        $states['MARYLAND'] = 'MD';
        $states['MASSACHUSETTS'] = 'MA';
        $states['MICHIGAN'] = 'MI';
        $states['MINNESOTA'] = 'MN';
        $states['MISSISSIPPI'] = 'MS';
        $states['MISSOURI'] = 'MO';
        $states['MONTANA'] = 'MT';
        $states['NEBRASKA'] = 'NE';
        $states['NEVADA'] = 'NV';
        $states['NEW HAMPSHIRE'] = 'NH';
        $states['NEW JERSEY'] = 'NJ';
        $states['NEW MEXICO'] = 'NM';
        $states['NEW YORK'] = 'NY';
        $states['NORTH CAROLINA'] = 'NC';
        $states['NORTH DAKOTA'] = 'ND';
        $states['NORTHERN MARIANA ISLANDS'] = 'MP';
        $states['OHIO'] = 'OH';
        $states['OKLAHOMA'] = 'OK';
        $states['OREGON'] = 'OR';
        $states['PALAU'] = 'PW';
        $states['PENNSYLVANIA'] = 'PA';
        $states['PUERTO RICO'] = 'PR';
        $states['RHODE ISLAND'] = 'RI';
        $states['SOUTH CAROLINA'] = 'SC';
        $states['SOUTH DAKOTA'] = 'SD';
        $states['TENNESSEE'] = 'TN';
        $states['TEXAS'] = 'TX';
        $states['UTAH'] = 'UT';
        $states['VERMONT'] = 'VT';
        $states['VIRGIN ISLANDS'] = 'VI';
        $states['VIRGINIA'] = 'VA';
        $states['WASHINGTON'] = 'WA';
        $states['WEST VIRGINIA'] = 'WV';
        $states['WISCONSIN'] = 'WI';
        $states['WYOMING'] = 'WY';

        // Canadian Provinces
        // edited 12-5-07
        $states['ALBERTA'] = 'AB';
        $states['BRITISH COLUMBIA'] = 'BC';
        $states['MANITOBA'] = 'MB';
        $states['NEW BRUNSWICK'] = 'NB';
        $states['LABRADOR'] = 'NL';
        $states['NEWFOUNDLAND'] = 'NL';
        $states['NORTHWEST TERRITORIES'] = 'NT';
        $states['NOVA SCOTIA'] = 'NS';
        $states['NUNAVUT'] = 'NU';
        $states['ONTARIO'] = 'ON';
        $states['PRINCE EDWARD ISLAND'] = 'PE';
        $states['QUEBEC'] = 'QC';
        $states['SASKATCHEWAN'] = 'SK';
        $states['YUKON'] = 'YT';

        return $states[strtoupper($state)];
    }
}
