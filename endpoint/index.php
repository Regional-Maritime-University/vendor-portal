<?php
session_start();

if (!isset($_SESSION["lastAccessed"])) $_SESSION["lastAccessed"] = time();
$_SESSION["currentAccess"] = time();

$diff = $_SESSION["currentAccess"] - $_SESSION["lastAccessed"];

if ($diff >  1800) die(json_encode(array("success" => false, "message" => "logout")));

/*
* Designed and programmed by
* @Author: Francis A. Anlimah
*/

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require "../bootstrap.php";

use Src\Controller\AdminController;
use Src\Controller\DownloadExcelDataController;
use Src\Controller\DownloadAllExcelDataController;
use Src\Controller\UploadExcelDataController;
use Src\Controller\ExposeDataController;

$expose = new ExposeDataController();
$admin = new AdminController();

$data = [];
$errors = [];

// All GET request will be sent here
if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if ($_GET["url"] == "programs") {
        if (isset($_GET["type"])) {
            $t = 0;
            if ($_GET["type"] != "All") {
                $t = (int) $_GET["type"];
            }
            $result = $admin->fetchPrograms($t);
            if (!empty($result)) {
                $data["success"] = true;
                $data["message"] = $result;
            } else {
                $data["success"] = false;
                $data["message"] = "No result found!";
            }
        }
        die(json_encode($data));
    } elseif ($_GET["url"] == "form-price") {
        if (!isset($_GET["form_key"]) || empty($_GET["form_key"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field")));
        }
        $rslt = $admin->fetchFormPrice($_GET["form_key"]);
        if (!$rslt) die(json_encode(array("success" => false, "message" => "Error fetching form price details!")));
        die(json_encode(array("success" => true, "message" => $rslt)));
    }
    //
    elseif ($_GET["url"] == "vendor-form") {
        if (!isset($_GET["vendor_key"]) || empty($_GET["vendor_key"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field")));
        }
        $rslt = $admin->fetchVendor($_GET["vendor_key"]);
        if (!$rslt) die(json_encode(array("success" => false, "message" => "Error fetching vendor details!")));
        die(json_encode(array("success" => true, "message" => $rslt)));
    }
    //
    elseif ($_GET["url"] == "prog-form") {
        if (!isset($_GET["prog_key"]) || empty($_GET["prog_key"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field")));
        }
        $rslt = $admin->fetchProgramme($_GET["prog_key"]);
        if (!$rslt) die(json_encode(array("success" => false, "message" => "Error fetching programme information!")));
        die(json_encode(array("success" => true, "message" => $rslt)));
    }
    //
    elseif ($_GET["url"] == "adp-form") {
        if (!isset($_GET["adp_key"]) || empty($_GET["adp_key"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field")));
        }
        $rslt = $admin->fetchAdmissionPeriod($_GET["adp_key"]);
        if (!$rslt) die(json_encode(array("success" => false, "message" => "Error fetching admissions information!")));
        die(json_encode(array("success" => true, "message" => $rslt)));
    }
    //
    elseif ($_GET["url"] == "user-form") {
        if (!isset($_GET["user_key"]) || empty($_GET["user_key"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field")));
        }
        $rslt = $admin->fetchSystemUser($_GET["user_key"]);
        if (!$rslt) die(json_encode(array("success" => false, "message" => "Error fetching user account information!")));
        die(json_encode(array("success" => true, "message" => $rslt)));
    }

    // All POST request will be sent here
} elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($_GET["url"] == "admin-login") {

        if (!isset($_SESSION["_adminLogToken"]) || empty($_SESSION["_adminLogToken"]))
            die(json_encode(array("success" => false, "message" => "Invalid request: 1!")));
        if (!isset($_POST["_vALToken"]) || empty($_POST["_vALToken"]))
            die(json_encode(array("success" => false, "message" => "Invalid request: 2!")));
        if ($_POST["_vALToken"] !== $_SESSION["_adminLogToken"]) {
            die(json_encode(array("success" => false, "message" => "Invalid request: 3!")));
        }

        $username = $expose->validateText($_POST["username"]);
        $password = $expose->validatePassword($_POST["password"]);

        $result = $admin->verifyAdminLogin($username, $password);

        if (!$result) {
            $_SESSION['adminLogSuccess'] = false;
            die(json_encode(array("success" => false, "message" => "Incorrect application username or password! ")));
        }

        $_SESSION['user'] = $result[0]["id"];
        $_SESSION['role'] = $result[0]["role"];
        $_SESSION['user_type'] = $result[0]["type"];
        $_SESSION["admin_period"] = $expose->getCurrentAdmissionPeriodID();

        if (strtoupper($result[0]['role']) == "VENDORS") {
            $_SESSION["vendor_id"] = $expose->getVendorPhoneByUserID($_SESSION["user"])[0]["id"];
        }

        $_SESSION['adminLogSuccess'] = true;
        die(json_encode(array("success" => true,  "message" => strtolower($result[0]["role"]))));
    }

    // set admission period
    elseif ($_GET["url"] == "set-admission-period") {
        if (!isset($_POST["data"])) die(json_encode(array("success" => false, "message" => "Invalid request!")));
        if (empty($_POST["data"])) die(json_encode(array("success" => false, "message" => "Missing input in request!")));
        $_SESSION["admin_period"] = (int) $_POST["data"];
        die(json_encode(array("success" => true,  "message" => "Admisssion period changed!")));
    }

    // Resend verification code
    elseif ($_GET["url"] == "resend-code") {
    }

    // Get details on form
    elseif ($_GET["url"] == "formInfo") {
        if (!isset($_POST["form_id"]) || empty($_POST["form_id"])) {
            die(json_encode(array("success" => false, "message" => "Error: Form has not been set properly in database!")));
        }

        $form_id = $expose->validateInput($_POST["form_id"]);
        $result = $expose->getFormPriceA($form_id);

        if (empty($result)) die(json_encode(array("success" => false, "message" => "Forms' price has not set in the database!")));
        die(json_encode(array("success" => true, "message" => $result)));
    }

    //Vendor endpoint
    elseif ($_GET["url"] == "sellAction") {
        if (isset($_SESSION["_vendor1Token"]) && !empty($_SESSION["_vendor1Token"]) && isset($_POST["_v1Token"]) && !empty($_POST["_v1Token"]) && $_POST["_v1Token"] == $_SESSION["_vendor1Token"]) {

            if (!isset($_POST["first_name"]) || empty($_POST["first_name"])) {
                die(json_encode(array("success" => false, "message" => "Customer first name is required!")));
            }
            if (!isset($_POST["last_name"]) || empty($_POST["last_name"])) {
                die(json_encode(array("success" => false, "message" => "Customer last name is required!")));
            }
            if (!isset($_POST["formSold"]) || empty($_POST["formSold"])) {
                die(json_encode(array("success" => false, "message" => "Choose a type of form to sell!")));
            }
            if (!isset($_POST["country"]) || empty($_POST["country"])) {
                die(json_encode(array("success" => false, "message" => "Phone number's country code is required!")));
            }
            if (!isset($_POST["phone_number"]) || empty($_POST["phone_number"])) {
                die(json_encode(array("success" => false, "message" => "Customer's phone number is required!")));
            }

            $first_name = $expose->validateText($_POST["first_name"]);
            $last_name = $expose->validateText($_POST["last_name"]);
            $phone_number = $expose->validatePhone($_POST["phone_number"]);
            $country = $expose->validateCountryCode($_POST["country"]);
            $form_id = $expose->validateNumber($_POST["formSold"]);
            //$form_type = $expose->validateNumber($_POST["form_type"]);
            $form_price = $_POST["form_price"];

            $charPos = strpos($country, ")");
            $country_name = substr($country, ($charPos + 2));
            $country_code = substr($country, 1, ($charPos - 1));

            $_SESSION["vendorData"] = array(
                "first_name" => $first_name,
                "last_name" => $last_name,
                "country_name" => $country_name,
                "country_code" => $country_code,
                "phone_number" => $phone_number,
                "email_address" => "",
                "form_id" => $form_id,
                //"form_type" => $form_type,
                "pay_method" => "CASH",
                "amount" => $form_price,
                "vendor_id" => $_SESSION["vendor_id"],
                "admin_period" => $_SESSION["admin_period"]
            );

            if (!isset($_SESSION["vendorData"]) || empty($_SESSION["vendorData"]))
                die(json_encode(array("success" => false, "message" => "Failed in preparing data payload submitted!")));

            if (!$expose->vendorExist($_SESSION["vendorData"]["vendor_id"]))
                die(json_encode(array("success" => false, "message" => "Process can only be performed by a vendor!")));

            die(json_encode($admin->processVendorPay($_SESSION["vendorData"])));
        } else {
            die(json_encode(array("success" => false, "message" => "Invalid request!")));
        }
    }

    // International student ref number verification
    elseif ($_GET["url"] == "ref-number-verify") {
        if (isset($_SESSION["_foreignFormToken"]) && !empty($_SESSION["_foreignFormToken"]) && isset($_POST["_FFToken"]) && !empty($_POST["_FFToken"]) && $_POST["_FFToken"] == $_SESSION["_foreignFormToken"]) {

            if (!isset($_POST["ref_number"]) || empty($_POST["ref_number"])) {
                die(json_encode(array("success" => false, "message" => "Reference Number is required!")));
            }

            $ref_number = $expose->validateText($_POST["ref_number"]);
            $result = $admin->verifyInternationalApplicantRefNumber($ref_number);
            if (empty($result)) die(json_encode(array("success" => false, "message" => "No match found for provided reference number!")));
            die(json_encode(array("success" => true, "message" => $result)));
        } else {
            die(json_encode(array("success" => false, "message" => "Invalid request!")));
        }
    }

    // International student ref number verification
    elseif ($_GET["url"] == "sell-international-form") {
        if (isset($_SESSION["_foreignFormToken"]) && !empty($_SESSION["_foreignFormToken"]) && isset($_POST["_FFToken"]) && !empty($_POST["_FFToken"]) && $_POST["_FFToken"] == $_SESSION["_foreignFormToken"]) {

            if (!isset($_POST["ref_number"]) || empty($_POST["ref_number"])) {
                die(json_encode(array("success" => false, "message" => "Reference Number is required!")));
            }

            $ref_number = $expose->validateText($_POST["ref_number"]);
            die(json_encode($admin->sellInternationalForm($ref_number)));
        } else {
            die(json_encode(array("success" => false, "message" => "Invalid request!")));
        }
    }

    //
    elseif ($_GET["url"] == "apps-data") {
    }
    //
    elseif ($_GET["url"] == "applicants") {
    }

    //
    else if ($_GET["url"] == "checkPrintedDocument") {
    }

    //
    elseif ($_GET["url"] == "getAllAdmittedApplicants") {
    }

    //
    elseif ($_GET["url"] == "getAllDeclinedApplicants") {
    }

    //
    elseif ($_GET["url"] == "getUnadmittedApps") {
    }
    //
    elseif ($_GET["url"] == "admitAll") {
    }
    //
    elseif ($_GET["url"] == "downloadBS") {
    }
    //
    elseif ($_GET["url"] == "getBroadsheetData") {
    }
    //
    elseif ($_GET["url"] == "downloadAwaiting") {
        $url = "../download-awaiting-ds.php?a=as&c=awaiting&ap=" . $_SESSION['admin_period'];
        die(json_encode(array("success" => true, "message" => $url)));
    }
    //
    elseif ($_GET["url"] == "extra-awaiting-data") {
    }

    ///
    elseif ($_GET["url"] == "form-price") {
    }

    //
    elseif ($_GET["url"] == "vendor-sub-branches-group") {
    }

    //
    elseif ($_GET["url"] == "vendor-sub-branches") {
    }
    //
    elseif ($_GET["url"] == "vendor-form") {
    }
    //
    elseif ($_GET["url"] == "prog-form") {
    }

    //
    elseif ($_GET["url"] == "adp-form-verify" && $_POST["adp-action"] == 'add') {
        if (!isset($_POST["adp-start"]) || empty($_POST["adp-start"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field: Start Date")));
        }
        if (!isset($_POST["adp-end"]) || empty($_POST["adp-end"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field: End Date")));
        }
        if (!isset($_POST["adp-desc"])) {
            die(json_encode(array("success" => false, "message" => "Missing input field: Description")));
        }

        $desc = '';
        if (isset($_POST["adp-desc"]) && !empty($_POST["adp-desc"])) $desc = $_POST["adp-desc"];

        if ($admin->fetchCurrentAdmissionPeriod()) {
            die(json_encode(array(
                "success" => false,
                "message" => "An admission period is currently open! Do you want to still continue?"
            )));
        }
        die(json_encode(array("success" => true, "message" => "add")));
    }

    //
    elseif ($_GET["url"] == "adp-form") {
    }
    //
    elseif ($_GET["url"] == "user-form") {
    }

    // For sales report on accounts dashboard
    elseif ($_GET["url"] == "salesReport") {
        if (!isset($_POST["admission-period"])) die(json_encode(array("success" => false, "message" => "Invalid input request for admission period!")));
        if (!isset($_POST["from-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for from date!")));
        if (!isset($_POST["to-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for to date!")));
        if (!isset($_POST["form-type"])) die(json_encode(array("success" => false, "message" => "Invalid input request for form type!")));
        if (!isset($_POST["purchase-status"])) die(json_encode(array("success" => false, "message" => "Invalid input request for purchase status!")));
        if (!isset($_POST["payment-method"])) die(json_encode(array("success" => false, "message" => "Invalid input request for payment method!")));

        if ((!empty($_POST["from-date"]) && empty($_POST["to-date"])) || (!empty($_POST["to-date"]) && empty($_POST["from-date"])))
            die(json_encode(array("success" => false, "message" => "Date range (From - To) must be set!")));

        $result = $admin->fetchAllFormPurchases($_SESSION["admin_period"], $_POST);
        if (empty($result)) die(json_encode(array("success" => false, "message" => "No result found for given parameters!")));
        die(json_encode(array("success" => true, "message" => $result)));
    }

    // For sales report on vendor's dashboard
    elseif ($_GET["url"] == "vendorSalesReport") {
        if (!isset($_POST["admission-period"])) die(json_encode(array("success" => false, "message" => "Invalid input request for admission period!")));
        if (!isset($_POST["from-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for from date!")));
        if (!isset($_POST["to-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for to date!")));
        if (!isset($_POST["form-type"])) die(json_encode(array("success" => false, "message" => "Invalid input request for form type!")));
        if (!isset($_POST["purchase-status"])) die(json_encode(array("success" => false, "message" => "Invalid input request for purchase status!")));

        if ((!empty($_POST["from-date"]) && empty($_POST["to-date"])) || (!empty($_POST["to-date"]) && empty($_POST["from-date"])))
            die(json_encode(array("success" => false, "message" => "Date range (From - To) must be set!")));

        $_POST["vendor-id"] = $_SESSION["vendor_id"];

        $result = $admin->fetchAllVendorFormPurchases($_SESSION["admin_period"], $_POST);
        if (empty($result)) die(json_encode(array("success" => false, "message" => "No result found for given parameters!")));
        die(json_encode(array("success" => true, "message" => $result)));
    }

    //
    elseif ($_GET["url"] == "purchaseInfo") {
        if (!isset($_POST["_data"]) || empty($_POST["_data"]))
            die(json_encode(array("success" => false, "message" => "Invalid request!")));
        $transID = $expose->validateNumber($_POST["_data"]);
        $result = $admin->fetchFormPurchaseDetailsByTranID($transID);
        if (empty($result)) die(json_encode(array("success" => false, "message" => "No result found!")));
        die(json_encode(array("success" => true, "message" => $result)));
    }

    // send purchase info
    elseif ($_GET["url"] == "gen-send-purchase-info") {
        if (!isset($_POST["genSendTransID"]) || empty($_POST["genSendTransID"]))
            die(json_encode(array("success" => false, "message" => "Invalid request!")));
        $transID = $expose->validateNumber($_POST["genSendTransID"]);
        die(json_encode($admin->sendPurchaseInfo($transID)));
    }

    // send purchase info
    elseif ($_GET["url"] == "send-purchase-info") {
        if (!isset($_POST["sendTransID"]) || empty($_POST["sendTransID"]))
            die(json_encode(array("success" => false, "message" => "Invalid request!")));
        $transID = $expose->validateNumber($_POST["sendTransID"]);
        die(json_encode($admin->sendPurchaseInfo($transID, false)));
    }

    // send purchase info
    elseif ($_GET["url"] == "verify-transaction-status") {
        if (!isset($_POST["verifyTransID"]) || empty($_POST["verifyTransID"]))
            die(json_encode(array("success" => false, "message" => "Invalid request:  transaction!")));
        if (!isset($_POST["payMethod"]) || empty($_POST["payMethod"]))
            die(json_encode(array("success" => false, "message" => "Invalid request: payment method!")));
        $transID = $expose->validateNumber($_POST["verifyTransID"]);
        die(json_encode($admin->verifyTransactionStatus($_POST["payMethod"], $transID, false)));
    }

    // send an sms to customer
    elseif ($_GET["url"] == "sms-customer") {
    }

    // fetch group sales data
    elseif ($_GET["url"] == "group-sales-report") {
        if (!isset($_POST["from-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for from date!")));
        if (!isset($_POST["to-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for to date!")));
        if (!isset($_POST["report-by"])) die(json_encode(array("success" => false, "message" => "Invalid input request for filter by!")));

        if ((!empty($_POST["from-date"]) && empty($_POST["to-date"])) || (!empty($_POST["to-date"]) && empty($_POST["from-date"])))
            die(json_encode(array("success" => false, "message" => "Date range (From - To) not set!")));

        $_data = $expose->validateText($_POST["report-by"]);
        $result = $admin->fetchFormPurchasesGroupReport($_POST);
        if (empty($result)) die(json_encode(array("success" => false, "message" => "No result found for given parameters!")));
        die(json_encode(array("success" => true, "message" => $result)));
    }

    // fetch group sales data
    elseif ($_GET["url"] == "group-sales-report-list") {
        if (!isset($_POST["_dataI"]) || empty($_POST["_dataI"])) die(json_encode(array("success" => false, "message" => "Invalid input request!")));
        if (!isset($_POST["from-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for from date!")));
        if (!isset($_POST["to-date"])) die(json_encode(array("success" => false, "message" => "Invalid input request for to date!")));
        if (!isset($_POST["report-by"])) die(json_encode(array("success" => false, "message" => "Invalid input request for filter by!")));

        if ((!empty($_POST["from-date"]) && empty($_POST["to-date"])) || (!empty($_POST["to-date"]) && empty($_POST["from-date"])))
            die(json_encode(array("success" => false, "message" => "Date range (From - To) not set!")));

        $_dataI = $expose->validateNumber($_POST["_dataI"]);
        $result = $admin->fetchFormPurchasesGroupReportInfo($_POST);
        if (empty($result)) die(json_encode(array("success" => false, "message" => "No result found for given parameters!")));
        die(json_encode(array("success" => true, "message" => $result)));
    }

    // download PDF
    elseif ($_GET["url"] == "download-file") {
        $result = $admin->prepareDownloadQuery($_POST);
        if (!$result) die(json_encode(array("success" => false, "message" => "Fatal error: server generated error!")));
        die(json_encode(array("success" => true, "message" => "successfully!")));
    } else if ($_GET["url"] == "general-download") {
    }

    // backup database
    elseif ($_GET["url"] == "backup-data") {
    }

    // reset password
    elseif ($_GET["url"] == "reset-password") {
        if (!isset($_POST["currentPassword"]) || empty($_POST["currentPassword"]))
            die(json_encode(array("success" => false, "message" => "Current password field is required!")));
        if (!isset($_POST["newPassword"]) || empty($_POST["newPassword"]))
            die(json_encode(array("success" => false, "message" => "New password field is required!")));
        if (!isset($_POST["renewPassword"]) || empty($_POST["renewPassword"]))
            die(json_encode(array("success" => false, "message" => "Retype new password field is required!")));

        $currentPass = $expose->validatePassword($_POST["currentPassword"]);
        $newPass = $expose->validatePassword($_POST["newPassword"]);
        $renewPass = $expose->validatePassword($_POST["renewPassword"]);

        if ($newPass !== $renewPass) die(json_encode(array("success" => false, "message" => "New password entry mismatched!")));

        $userDetails = $admin->verifySysUserExistsByID($_SESSION["user"]);
        if (empty($userDetails)) die(json_encode(array("success" => false, "message" => "Failed to verify user account!")));

        $result = $admin->verifyAdminLogin($userDetails[0]["user_name"], $currentPass);
        if (!$result) die(json_encode(array("success" => false, "message" => "Incorrect current password!")));

        $changePassword = $admin->resetUserPassword($_SESSION["user"], $newPass);
        die(json_encode($changePassword));
    }

    // admit an applicant to a particular programme and generate admission letter
    elseif ($_GET["url"] == "admit-individual-applicant") {
    }

    // decline applicant admission
    elseif ($_GET["url"] == "decline-individual-applicant") {
    }

    // Send admission letter to applicant
    elseif ($_GET["url"] == "send-admission-files") {
    }

    // Enroll applicant
    elseif ($_GET["url"] == "enroll-applicant") {
    }

    //
    elseif ($_GET["url"] == "unenroll-applicant") {
    }

    ///
    elseif ($_GET["url"] == "export-excel") {
    }

    //
    else if ($_GET["url"] == "generateNewAPIKeys") {
        if (!isset($_POST["__generateAPIKeys"]) || empty($_POST["__generateAPIKeys"]))
            die(json_encode(array("success" => false, "message" => "Invalid request received!")));

        die(json_encode($admin->generateAPIKeys($_SESSION["vendor_id"])));
    }

    // All PUT request will be sent here
} else if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    parse_str(file_get_contents("php://input"), $_PUT);

    if ($_GET["url"] == "adp-form") {
    }

    // All DELETE request will be sent here
} else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    parse_str(file_get_contents("php://input"), $_DELETE);

    if ($_GET["url"] == "form-price") {
    }

    if ($_GET["url"] == "vendor-form") {
    }

    if ($_GET["url"] == "prog-form") {
    }

    if ($_GET["url"] == "user-form") {
    }
} else {
    http_response_code(405);
}
