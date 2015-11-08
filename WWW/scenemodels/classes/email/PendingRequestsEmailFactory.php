<?php

/*
 * Copyright (C) 2015 julien
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace email;

/**
 * Description of PendingRequestsEmailFactory
 *
 * @author julien
 */
class PendingRequestsEmailFactory extends EmailContentFactory {
    static public function getPendingRequestsEmailContent($requests, $invalidRequests) {
        $subject = "Pending requests";
        $pendingRequests = "";
        
        // List all requests
        foreach ($requests as $request) {
            $sig = $request->getSig();
            $pendingRequests .= "\nRequest #".$request->getId()."\n";
            $pendingRequests .= "=========================================================================================\n";

            switch (get_class($request)) {
            case "model\RequestObjectUpdate":
                $pendingRequests .= "This is an object update request!\n";
                $pendingRequests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=ObjectValidator&a=viewRequest&sig=".$sig."\n";
                break;

            case "model\RequestObjectDelete":
                $pendingRequests .= "This is an object deletion request!\n";
                $pendingRequests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=ObjectValidator&a=viewRequest&sig=".$sig."\n";
                break;

            case "model\RequestMassiveObjectsAdd":
                $pendingRequests .= "This is an object(s) addition request!\n";
                $pendingRequests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=AddObjectsValidator&a=viewRequest&sig=".$sig."\n";
                break;

            case "model\RequestModelAdd":
                $pendingRequests .= "This is a 3D model addition request!\n";
                $pendingRequests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=AddModelValidator&a=viewRequest&sig=".$sig."\n";
                break;

            case "model\RequestModelUpdate":
                $pendingRequests .= "This is a 3D model update request!\n";
                $pendingRequests .= "http://".$_SERVER['SERVER_NAME']."/app.php?c=UpdateModelValidator&a=viewRequest&sig=".$sig."\n";
                break;
            }
        }
        
        $invalidReqText = "";
        foreach ($invalidRequests as $invalidReq) {
            $invalidReqText .= "Request #".$invalidReq->getId()." is invalid! ".$invalidReq->getComment()."\n";
            $invalidReqText .= "To delete it, click: http://".$_SERVER['SERVER_NAME']."/app.php?c=GenericValidator&a=rejectRequest&sig=".$sig."\r\n\r\n";
        }
        
        $message = "We would like to give you an overview of the remaining pending requests.\r\n\r\n" .
                   $pendingRequests . "\r\n\r\n" .
                   "These are invalid requests :\r\n" .
                   $invalidReqText . "\r\n\r\n" .
                   "They should be somewhere in your mails. Please check again.\r\n\r\n";
        echo $message;
        return new \email\EmailContent($subject, self::format($message));
    }
    
    static public function getPendingRequestsNoneEmailContent() {
        $subject = "Pending requests";
        $message = "There are currently no pending requests. Well done! Hopefully some more will come soon ;-).\r\n\r\n";
            
        return new \email\EmailContent($subject, self::format($message));
    }
}