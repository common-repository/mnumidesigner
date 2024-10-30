Available shortcodes:
=====================

Edit project url
----------------

[mnumidesigner_edit_project_url project="" back_url="" templates="" ping_url="" translation_id="" calendar_ids=""]

Outputs edit url to MnumiDesigner project

Attributes:
* project       - required, ID of project for which edit url is generated
* back_url      - required, url to return to from MnumiDesigner
* templates     - required, available templates
* ping_url      - optional, url to which background pinging will be made.
                  Used for refreshing session
* translation_id- optional, ID of translation which will be used in
                  MnumiDesigner editor
* calendar_ids  - optional, comma separated IDs of calendars which will be used in
                  MnumiDesigner editor

Edit project link
-----------------

[mnumidesigner_edit_project_url project="" back_url="" templates="" ping_url="" translation_id="" calendar_ids="" text="" class=""]

Outputs HTML edit link to MnumiDesigner project:

Attributes:
* project       - required, ID of project for which edit url is generated
* back_url      - required, url to return to from MnumiDesigner
* templates     - required, available templates
* ping_url      - optional, url to which background pinging will be made.
                  Used for refreshing session refreshing session
* translation_id- optional, ID of translation which will be used in
                  MnumiDesigner editor
* calendar_ids  - optional, comma separated IDs of calendars which will be used in
                  MnumiDesigner editor
* text          - Text which will be displayed in link
* class         - Anchor class

Initialize & Check PDF generation url
-------------------------------------

[mnumidesigner_project_pdf_check_url project="" barcode="" translation_id="" calendar_ids="" id="" range=""]

Outputs url to initialize and/or check if PDF for MnumiDesigner project is generated.

Attributes:
* project       - required, ID of project for which edit url is generated
* barcode       - optional, value used for generating barcode
* translation_id- optional, ID of translation which will be used in
                  MnumiDesigner editor
* calendar_ids  - optional, comma separated IDs of calendars which will be used in
* id            - optional, 
* range         - optional, 

Download PDF generation url
---------------------------

[mnumidesigner_project_pdf_status_url project="" barcode="" translation_id="" calendar_ids="" id="" range=""]

Outputs url to download generated MnumiDesigner project PDF.

Attributes:
* project       - required, ID of project for which edit url is generated
* barcode       - optional, value used for generating barcode
* translation_id- optional, ID of translation which will be used in
                  MnumiDesigner editor
* calendar_ids  - optional, comma separated IDs of calendars which will be used in
* id            - optional, 
* range         - optional, 


Available WooCommerce shortcodes:
=================================

New project url
---------------

[mnumidesigner_wc_new_project_url product_id="" variation_id="" count="" back_url="" ping_url="" translation_id="" calendar_ids=""]

Outputs url allowing creation of new MnumiDesigner projects.
Available templates are based on passed product_id & variation_id

Attributes:
* product_id    - required, ID of product for which new project url is generated
* variation_id  - optional, variation ID of the product
* count         - required, Number of pages in project that it will be created.
                  Note, that this attribute will be ignored for calendar based
                  templates  (default: passed product's default pages number)
* back_url      - required, url to return to from MnumiDesigner
* ping_url      - optional, url to which background pinging will be made.
                  Used for refreshing session refreshing session
* translation_id- optional, ID of translation which will be used in
                  MnumiDesigner editor
* calendar_ids  - optional, comma separated IDs of calendars which will be used in
                  MnumiDesigner editor


Edit project url
----------------

[mnumidesigner_wc_new_project_link product_id="" variation_id="" count="" back_url="" ping_url="" translation_id="" calendar_ids=""]

Outputs link to MnumiDesigner based currently viewed product

Attributes:

* product_id    - required, ID of product for which new project url is generated
* variation_id  - optional, variation ID of the product
* count         - required, Number of pages in project that it will be created.
                  Note, that this attribute will be ignored for calendar based
                  templates  (default: passed product's default pages number)
* back_url      - required, url to return to from MnumiDesigner
* ping_url      - optional, url to which background pinging will be made.
                  Used for refreshing session refreshing session
* translation_id- optional, ID of translation which will be used in
                  MnumiDesigner editor
* calendar_ids  - optional, comma separated IDs of calendars which will be used in
                  MnumiDesigner editor
* text          - Text which will be displayed in link
* class         - Anchor class
