<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = [];
        $perPage = 10;
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        if($request->startDate && $request->endDate){
            $userId = Auth::user()->id == 15 ? 79 : Auth::user()->id;
            // $userId = 125;
            $startDate = date('Y-m-d 00:00:00', strtotime($request->startDate));
            $endDate = date('Y-m-d 23:59:59', strtotime($request->endDate));
            $reports = DB::select("
                WITH base_data AS (
                    SELECT
                        down.idUpline,
                        down.idDevice,
                        down.name,
                        down.phone,
                        down.user_id,
                        down.user_name,
                        down.uuid,
                        send_reply.totSend,
                        send_reply.totReply,
                        first_send.byCustomer,
                        first_send.bySales,
                        first_send.timeReply,
                        contact.totContacts,
                        CASE
                            WHEN COALESCE(first_send.timeReply, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.timeReply, 0) /
                COALESCE(first_send.byCustomer+first_send.bySales, 0)) *
                100, 2)
                        END AS minuteReplyPercent,
                        CASE
                            WHEN COALESCE(contact.totContacts, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(send_reply.totSend, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendPercent,
                        CASE
                            WHEN COALESCE(send_reply.totSend, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(send_reply.totReply, 0) /
                COALESCE(send_reply.totSend, 0)) * 100, 2)
                        END AS replyPercent,
                        CASE
                            WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.byCustomer, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendFirstCustomerPercent,
                        CASE
                            WHEN COALESCE(first_send.bySales, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.bySales, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendFirstSalesPercent,
                        DATEDIFF(?, ?) + 1 AS totDays
                    FROM
                        (SELECT
                            dw.user_id AS idUpline,
                            dv.id AS idDevice,
                            dv.name,
                            dv.phone,
                            dv.user_id,
                            dv.user_name,
                            dv.uuid
                        FROM downlines dw
                        INNER JOIN devices dv ON dv.user_id =
                dw.downline_user_id
                        WHERE
                            dw.user_id = ?
                            AND dv.status = 1
                        ORDER BY dv.id DESC
                        ) down
                    LEFT JOIN (
                        SELECT
                            ch1.device_id,
                            SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0
                END) AS totSend,
                            SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0
                END) AS totReply
                        FROM
                            (SELECT
                                device_id,contact_id,MIN(created_at),fromMe
                            FROM chats
                            WHERE
                                created_at BETWEEN ? AND ?
                            GROUP BY device_id,contact_id,fromMe) ch1
                        GROUP BY ch1.device_id
                    ) send_reply ON send_reply.device_id = down.idDevice
                    LEFT JOIN (
                        WITH FirstSendData AS (
                            SELECT
                                contact_id,
                                device_id,
                                CASE
                                    WHEN MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) <
                                        MIN(CASE WHEN fromMe = 'false' THEN
                created_at END) THEN 'sales'
                                    WHEN MIN(CASE WHEN fromMe = 'false' THEN
                created_at END) <
                                        MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) THEN 'customer'
                                    WHEN MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) IS NOT NULL THEN 'sales'
                                    ELSE 'customer'
                                END AS firstSendBy,
                                IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN
                fromMe = 'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END))<0,
                                TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
                'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END)) *-1,
                                TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
                'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END))) AS timeReply
                            FROM
                                chats
                            WHERE
                                created_at BETWEEN ? AND ?
                            GROUP BY
                                contact_id, device_id
                        )
                        SELECT
                            device_id,
                            COUNT(CASE WHEN firstSendBy = 'sales' THEN 1
                END) AS bySales,
                            COUNT(CASE WHEN firstSendBy = 'customer' THEN 1
                END) AS byCustomer,
                            SUM(timeReply) AS timeReply
                        FROM
                            FirstSendData
                        GROUP BY
                            device_id
                        ORDER BY
                            device_id ASC
                    ) first_send ON first_send.device_id = down.idDevice
                    LEFT JOIN (
                        SELECT
                            device_id,
                            COUNT(*) AS totContacts
                        FROM contacts
                        GROUP BY device_id
                    ) contact ON contact.device_id = down.idDevice
                    WHERE down.idDevice IS NOT NULL

                    UNION

                    SELECT
                        down.idUpline,
                        down.idDevice,
                        down.name,
                        down.phone,
                        down.user_id,
                        down.user_name,
                        down.uuid,
                        send_reply.totSend,
                        send_reply.totReply,
                        first_send.byCustomer,
                        first_send.bySales,
                        first_send.timeReply,
                        contact.totContacts,
                        CASE
                            WHEN COALESCE(first_send.timeReply, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.timeReply, 0) /
                COALESCE(first_send.byCustomer+first_send.bySales, 0)) *
                100, 2)
                        END AS minuteReplyPercent,
                        CASE
                            WHEN COALESCE(contact.totContacts, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(send_reply.totSend, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendPercent,
                        CASE
                            WHEN COALESCE(send_reply.totSend, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(send_reply.totReply, 0) /
                COALESCE(send_reply.totSend, 0)) * 100, 2)
                        END AS replyPercent,
                        CASE
                            WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.byCustomer, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendFirstCustomerPercent,
                        CASE
                            WHEN COALESCE(first_send.bySales, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.bySales, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendFirstSalesPercent,
                        DATEDIFF(?, ?) + 1 AS totDays
                    FROM
                        (SELECT
                            dw.user_id AS idUpline,
                            dv.id AS idDevice,
                            dv.name,
                            dv.phone,
                            dv.user_id,
                            dv.user_name,
                            dv.uuid
                        FROM devices dv
                        LEFT JOIN downlines dw ON dv.user_id =
                dw.downline_user_id AND dw.user_id = ?
                        WHERE
                            dv.status = 1
                            AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
                        ORDER BY dv.id DESC
                        ) down
                    LEFT JOIN (
                        SELECT
                            ch1.device_id,
                            SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0
                END) AS totSend,
                            SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0
                END) AS totReply
                        FROM
                            (SELECT
                                device_id,contact_id,MIN(created_at),fromMe
                            FROM chats
                            WHERE
                                created_at BETWEEN ? AND ?
                            GROUP BY device_id,contact_id,fromMe) ch1
                        GROUP BY ch1.device_id
                    ) send_reply ON send_reply.device_id = down.idDevice
                    LEFT JOIN (
                        WITH FirstSendData AS (
                            SELECT
                                contact_id,
                                device_id,
                                CASE
                                    WHEN MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) <
                                        MIN(CASE WHEN fromMe = 'false' THEN
                created_at END) THEN 'sales'
                                    WHEN MIN(CASE WHEN fromMe = 'false' THEN
                created_at END) <
                                        MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) THEN 'customer'
                                    WHEN MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) IS NOT NULL THEN 'sales'
                                    ELSE 'customer'
                                END AS firstSendBy,
                                IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN
                fromMe = 'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END))<0,
                                TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
                'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END)) *-1,
                                TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
                'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END))) AS timeReply
                            FROM
                                chats
                            WHERE
                                created_at BETWEEN ? AND ?
                            GROUP BY
                                contact_id, device_id
                        )
                        SELECT
                            device_id,
                            COUNT(CASE WHEN firstSendBy = 'sales' THEN 1
                END) AS bySales,
                            COUNT(CASE WHEN firstSendBy = 'customer' THEN 1
                END) AS byCustomer,
                            SUM(timeReply) AS timeReply
                        FROM
                            FirstSendData
                        GROUP BY
                            device_id
                        ORDER BY
                            device_id ASC
                    ) first_send ON first_send.device_id = down.idDevice
                    LEFT JOIN (
                        SELECT
                            device_id,
                            COUNT(*) AS totContacts
                        FROM contacts
                        GROUP BY device_id
                    ) contact ON contact.device_id = down.idDevice
                    WHERE down.user_id IS NULL AND down.idUpline IS NOT NULL

                    UNION

                    SELECT
                        down.idUpline,
                        down.idDevice,
                        down.name,
                        down.phone,
                        down.user_id,
                        down.user_name,
                        down.uuid,
                        send_reply.totSend,
                        send_reply.totReply,
                        first_send.byCustomer,
                        first_send.bySales,
                        first_send.timeReply,
                        contact.totContacts,
                        CASE
                            WHEN COALESCE(first_send.timeReply, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.timeReply, 0) /
                COALESCE(first_send.byCustomer+first_send.bySales, 0)) *
                100, 2)
                        END AS minuteReplyPercent,
                        CASE
                            WHEN COALESCE(contact.totContacts, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(send_reply.totSend, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendPercent,
                        CASE
                            WHEN COALESCE(send_reply.totSend, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(send_reply.totReply, 0) /
                COALESCE(send_reply.totSend, 0)) * 100, 2)
                        END AS replyPercent,
                        CASE
                            WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.byCustomer, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendFirstCustomerPercent,
                        CASE
                            WHEN COALESCE(first_send.bySales, 0) = 0 THEN
                0.00
                            ELSE ROUND((COALESCE(first_send.bySales, 0) /
                COALESCE(contact.totContacts, 0)) * 100, 2)
                        END AS sendFirstSalesPercent,
                        DATEDIFF(?, ?) + 1 AS totDays
                    FROM
                        (SELECT
                            dw.user_id AS idUpline,
                            dv.id AS idDevice,
                            dv.name,
                            dv.phone,
                            dv.user_id,
                            dv.user_name,
                            dv.uuid
                        FROM devices dv
                        LEFT JOIN downlines dw ON dv.user_id =
                dw.downline_user_id AND dw.user_id = ?
                        WHERE
                            dv.status = 1
                            AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
                        ORDER BY dv.id DESC
                        ) down
                    LEFT JOIN (
                        SELECT
                            ch1.device_id,
                            SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0
                END) AS totSend,
                            SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0
                END) AS totReply
                        FROM
                            (SELECT
                                device_id,contact_id,MIN(created_at),fromMe
                            FROM chats
                            WHERE
                                created_at BETWEEN ? AND ?
                            GROUP BY device_id,contact_id,fromMe) ch1
                        GROUP BY ch1.device_id
                    ) send_reply ON send_reply.device_id = down.idDevice
                    LEFT JOIN (
                        WITH FirstSendData AS (
                            SELECT
                                contact_id,
                                device_id,
                                CASE
                                    WHEN MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) <
                                        MIN(CASE WHEN fromMe = 'false' THEN
                created_at END) THEN 'sales'
                                    WHEN MIN(CASE WHEN fromMe = 'false' THEN
                created_at END) <
                                        MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) THEN 'customer'
                                    WHEN MIN(CASE WHEN fromMe = 'true' THEN
                created_at END) IS NOT NULL THEN 'sales'
                                    ELSE 'customer'
                                END AS firstSendBy,
                                IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN
                fromMe = 'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END))<0,
                                TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
                'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END)) *-1,
                                TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
                'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN
                created_at END))) AS timeReply
                            FROM
                                chats
                            WHERE
                                created_at BETWEEN ? AND ?
                            GROUP BY
                                contact_id, device_id
                        )
                        SELECT
                            device_id,
                            COUNT(CASE WHEN firstSendBy = 'sales' THEN 1
                END) AS bySales,
                            COUNT(CASE WHEN firstSendBy = 'customer' THEN 1
                END) AS byCustomer,
                            SUM(timeReply) AS timeReply
                        FROM
                            FirstSendData
                        GROUP BY
                            device_id
                        ORDER BY
                            device_id ASC
                    ) first_send ON first_send.device_id = down.idDevice
                    LEFT JOIN (
                        SELECT
                            device_id,
                            COUNT(*) AS totContacts
                        FROM contacts
                        GROUP BY device_id
                    ) contact ON contact.device_id = down.idDevice
                    WHERE down.idUpline IS NULL
                )
                SELECT
                    u.name as user,
                    b.*,
                    COUNT(*) OVER() as total_count
                FROM base_data b
                LEFT JOIN users u ON u.id = b.user_id
                LIMIT ? OFFSET ?
            ", [
                $endDate, $startDate,
                $userId,
                $startDate, $endDate,
                $startDate, $endDate,
                $endDate, $startDate,
                $userId, $userId,
                $startDate, $endDate,
                $startDate, $endDate,
                $endDate, $startDate,
                $userId, $userId,
                $startDate, $endDate,
                $startDate, $endDate,
                $perPage, $offset
            ]);
            // $reports = DB::select("
            //     WITH base_data AS (
            //         SELECT
            //             down.idUpline,
            //             down.idDevice,
            //             down.name,
            //             down.phone,
            //             down.user_id,
            //             down.user_name,
            //             down.uuid,
            //             send_reply.totSend,
            //             send_reply.totReply,
            //             first_send.byCustomer,
            //             first_send.bySales,
            //             first_send.timeReply,
            //             contact.totContacts,
            //             CASE
            //                 WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 0)) * 100, 2)
            //             END AS minuteReplyPercent,
            //             CASE
            //                 WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendPercent,
            //             CASE
            //                 WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 0)) * 100, 2)
            //             END AS replyPercent,
            //             CASE
            //                 WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendFirstCustomerPercent,
            //             CASE
            //                 WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendFirstSalesPercent,
            //             DATEDIFF(?, ?) + 1 AS totDays
            //         FROM
            //             (SELECT
            //                 dw.user_id AS idUpline,
            //                 dv.id AS idDevice,
            //                 dv.name,
            //                 dv.phone,
            //                 dv.user_id,
            //                 dv.user_name,
            //                 dv.uuid
            //             FROM downlines dw
            //             INNER JOIN devices dv ON dv.user_id = dw.downline_user_id
            //             WHERE
            //                 dw.user_id = ?
            //                 AND dv.status = 1
            //             ORDER BY dv.id DESC
            //             ) down
            //         LEFT JOIN (
            //             SELECT
            //                 ch1.user_id,
            //                 SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
            //                 SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
            //             FROM
            //                 (SELECT
            //                     user_id,contact_id,MIN(created_at),fromMe
            //                 FROM chats
            //                 WHERE            
            //                     created_at BETWEEN ? AND ?
            //                 GROUP BY user_id,contact_id,fromMe) ch1
            //             GROUP BY ch1.user_id
            //         ) send_reply ON send_reply.user_id = down.user_id
            //         LEFT JOIN (
            //             WITH FirstSendData AS (
            //                 SELECT
            //                     contact_id,
            //                     user_id,
            //                     CASE 
            //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) < 
            //                              MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
            //                         WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) < 
            //                              MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
            //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
            //                         ELSE 'customer'
            //                     END AS firstSendBy,
            //                     IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))<0,  
            //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END)) *-1,
            //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))) AS timeReply
            //                 FROM 
            //                     chats
            //                 WHERE            
            //                     created_at BETWEEN ? AND ?
            //                 GROUP BY 
            //                     contact_id, user_id
            //             )
            //             SELECT
            //                 user_id,
            //                 COUNT(CASE WHEN firstSendBy = 'sales' THEN 1 END) AS bySales,
            //                 COUNT(CASE WHEN firstSendBy = 'customer' THEN 1 END) AS byCustomer,
            //                 SUM(timeReply) AS timeReply
            //             FROM 
            //                 FirstSendData
            //             GROUP BY 
            //                 user_id
            //             ORDER BY 
            //                 user_id ASC
            //         ) first_send ON first_send.user_id = down.user_id
            //         LEFT JOIN (
            //             SELECT
            //                 user_id,
            //                 COUNT(*) AS totContacts
            //             FROM contacts
            //             GROUP BY user_id
            //         ) contact ON contact.user_id = down.user_id
            //         WHERE down.user_id IS NOT NULL

            //         UNION

            //         SELECT
            //             down.idUpline,
            //             down.idDevice,
            //             down.name,
            //             down.phone,
            //             down.user_id,
            //             down.user_name,
            //             down.uuid,
            //             send_reply.totSend,
            //             send_reply.totReply,
            //             first_send.byCustomer,
            //             first_send.bySales,
            //             first_send.timeReply,
            //             contact.totContacts,
            //             CASE
            //                 WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 0)) * 100, 2)
            //             END AS minuteReplyPercent,
            //             CASE
            //                 WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendPercent,
            //             CASE
            //                 WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 0)) * 100, 2)
            //             END AS replyPercent,
            //             CASE
            //                 WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendFirstCustomerPercent,
            //             CASE
            //                 WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendFirstSalesPercent,
            //             DATEDIFF(?, ?) + 1 AS totDays
            //         FROM
            //             (SELECT
            //                 dw.user_id AS idUpline,
            //                 dv.id AS idDevice,
            //                 dv.name,
            //                 dv.phone,
            //                 dv.user_id,
            //                 dv.user_name,
            //                 dv.uuid
            //             FROM devices dv
            //             LEFT JOIN downlines dw ON dv.user_id = dw.downline_user_id AND dw.user_id = ?
            //             WHERE
            //                 dv.status = 1
            //                 AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
            //             ORDER BY dv.id DESC
            //             ) down
            //         LEFT JOIN (
            //             SELECT
            //                 ch1.user_id,
            //                 SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
            //                 SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
            //             FROM
            //                 (SELECT
            //                     user_id,contact_id,MIN(created_at),fromMe
            //                 FROM chats
            //                 WHERE            
            //                     created_at BETWEEN ? AND ?
            //                 GROUP BY user_id,contact_id,fromMe) ch1
            //             GROUP BY ch1.user_id
            //         ) send_reply ON send_reply.user_id = down.idUpline
            //         LEFT JOIN (
            //             WITH FirstSendData AS (
            //                 SELECT
            //                     contact_id,
            //                     user_id,
            //                     CASE 
            //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) < 
            //                              MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
            //                         WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) < 
            //                              MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
            //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
            //                         ELSE 'customer'
            //                     END AS firstSendBy,
            //                     IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))<0,  
            //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END)) *-1,
            //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))) AS timeReply
            //                 FROM 
            //                     chats
            //                 WHERE            
            //                     created_at BETWEEN ? AND ?
            //                 GROUP BY 
            //                     contact_id, user_id
            //             )
            //             SELECT
            //                 user_id,
            //                 COUNT(CASE WHEN firstSendBy = 'sales' THEN 1 END) AS bySales,
            //                 COUNT(CASE WHEN firstSendBy = 'customer' THEN 1 END) AS byCustomer,
            //                 SUM(timeReply) AS timeReply
            //             FROM 
            //                 FirstSendData
            //             GROUP BY 
            //                 user_id
            //             ORDER BY 
            //                 user_id ASC
            //         ) first_send ON first_send.user_id = down.idUpline
            //         LEFT JOIN (
            //             SELECT
            //                 user_id,
            //                 COUNT(*) AS totContacts,phone
            //             FROM contacts
            //             GROUP BY user_id,phone
            //         ) contact ON contact.user_id = down.idUpline
            //         WHERE down.user_id IS NULL AND down.idUpline IS NOT NULL

            //         UNION

            //         SELECT
            //             down.idUpline,
            //             down.idDevice,
            //             down.name,
            //             down.phone,
            //             down.user_id,
            //             down.user_name,
            //             down.uuid,
            //             send_reply.totSend,
            //             send_reply.totReply,
            //             first_send.byCustomer,
            //             first_send.bySales,
            //             first_send.timeReply,
            //             contact.totContacts,
            //             CASE
            //                 WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 0)) * 100, 2)
            //             END AS minuteReplyPercent,
            //             CASE
            //                 WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendPercent,
            //             CASE
            //                 WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 0)) * 100, 2)
            //             END AS replyPercent,
            //             CASE
            //                 WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendFirstCustomerPercent,
            //             CASE
            //                 WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
            //                 ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
            //             END AS sendFirstSalesPercent,
            //             DATEDIFF(?, ?) + 1 AS totDays
            //         FROM
            //             (SELECT
            //                 dw.user_id AS idUpline,
            //                 dv.id AS idDevice,
            //                 dv.name,
            //                 dv.phone,
            //                 dv.user_id,
            //                 dv.user_name,
            //                 dv.uuid
            //             FROM devices dv
            //             LEFT JOIN downlines dw ON dv.user_id = dw.downline_user_id AND dw.user_id = ?
            //             WHERE
            //                 dv.status = 1
            //                 AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
            //             ORDER BY dv.id DESC
            //             ) down
            //         LEFT JOIN (
            //             SELECT
            //                 ch1.device_id,
            //                 SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
            //                 SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
            //             FROM
            //                 (SELECT
            //                     device_id,contact_id,MIN(created_at),fromMe
            //                 FROM chats
            //                 WHERE            
            //                     created_at BETWEEN ? AND ?
            //                 GROUP BY device_id,contact_id,fromMe) ch1
            //             GROUP BY ch1.device_id
            //         ) send_reply ON send_reply.device_id = down.idDevice
            //         LEFT JOIN (
            //             WITH FirstSendData AS (
            //                 SELECT
            //                     contact_id,
            //                     device_id,
            //                     CASE 
            //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) < 
            //                              MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
            //                         WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) < 
            //                              MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
            //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
            //                         ELSE 'customer'
            //                     END AS firstSendBy,
            //                     IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))<0,  
            //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END)) *-1,
            //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
            //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))) AS timeReply
            //                 FROM 
            //                     chats
            //                 WHERE            
            //                     created_at BETWEEN ? AND ?
            //                 GROUP BY 
            //                     contact_id, device_id
            //             )
            //             SELECT
            //                 device_id,
            //                 COUNT(CASE WHEN firstSendBy = 'sales' THEN 1 END) AS bySales,
            //                 COUNT(CASE WHEN firstSendBy = 'customer' THEN 1 END) AS byCustomer,
            //                 SUM(timeReply) AS timeReply
            //             FROM 
            //                 FirstSendData
            //             GROUP BY 
            //                 device_id
            //             ORDER BY 
            //                 device_id ASC
            //         ) first_send ON first_send.device_id = down.idDevice
            //         LEFT JOIN (
            //             SELECT
            //                 device_id,
            //                 COUNT(*) AS totContacts
            //             FROM contacts
            //             GROUP BY device_id
            //         ) contact ON contact.device_id = down.idDevice
            //         WHERE down.idUpline IS NULL
            //     )
            //     SELECT 
            //         u.name as user,
            //         b.*,
            //         COUNT(*) OVER() as total_count
            //     FROM base_data b
            //     LEFT JOIN users u ON u.id = b.user_id
            //     LIMIT ? OFFSET ?
            // ", [
            //     $endDate, $startDate,
            //     $userId,
            //     $startDate, $endDate,
            //     $startDate, $endDate,
            //     $endDate, $startDate,
            //     $userId, $userId,
            //     $startDate, $endDate,
            //     $startDate, $endDate,
            //     $endDate, $startDate,
            //     $userId, $userId,
            //     $startDate, $endDate,
            //     $startDate, $endDate,
            //     $perPage, $offset
            // ]);

            $total = $reports[0]->total_count ?? 0;
            $reports = new LengthAwarePaginator(
                $reports,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query()
                ]
            );
        };

        return view('user.report.index', compact('reports', 'request'));
    }

    public function export(Request $request)
    {
        if(!$request->startDate || !$request->endDate) {
            return response()->json([
                'message' => __('Please select date range'),
                'status' => 'error'
            ]);
        }

        $startDate = date('Y-m-d 00:00:00', strtotime($request->startDate));
        $endDate = date('Y-m-d 23:59:59', strtotime($request->endDate));

        $reports = DB::select("
            WITH base_data AS (
                SELECT
                    down.idUpline,
                    down.idDevice,
                    down.name,
                    down.phone,
                    down.user_id,
                    down.user_name,
                    down.uuid,
                    send_reply.totSend,
                    send_reply.totReply,
                    first_send.byCustomer,
                    first_send.bySales,
                    first_send.timeReply,
                    contact.totContacts,
                    CASE
                        WHEN COALESCE(first_send.timeReply, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.timeReply, 0) /
            COALESCE(first_send.byCustomer+first_send.bySales, 0)) *
            100, 2)
                    END AS minuteReplyPercent,
                    CASE
                        WHEN COALESCE(contact.totContacts, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(send_reply.totSend, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendPercent,
                    CASE
                        WHEN COALESCE(send_reply.totSend, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(send_reply.totReply, 0) /
            COALESCE(send_reply.totSend, 0)) * 100, 2)
                    END AS replyPercent,
                    CASE
                        WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.byCustomer, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendFirstCustomerPercent,
                    CASE
                        WHEN COALESCE(first_send.bySales, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.bySales, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendFirstSalesPercent,
                    DATEDIFF(?, ?) + 1 AS totDays
                FROM
                    (SELECT
                        dw.user_id AS idUpline,
                        dv.id AS idDevice,
                        dv.name,
                        dv.phone,
                        dv.user_id,
                        dv.user_name,
                        dv.uuid
                    FROM downlines dw
                    INNER JOIN devices dv ON dv.user_id =
            dw.downline_user_id
                    WHERE
                        dw.user_id = ?
                        AND dv.status = 1
                    ORDER BY dv.id DESC
                    ) down
                LEFT JOIN (
                    SELECT
                        ch1.device_id,
                        SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0
            END) AS totSend,
                        SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0
            END) AS totReply
                    FROM
                        (SELECT
                            device_id,contact_id,MIN(created_at),fromMe
                        FROM chats
                        WHERE
                            created_at BETWEEN ? AND ?
                        GROUP BY device_id,contact_id,fromMe) ch1
                    GROUP BY ch1.device_id
                ) send_reply ON send_reply.device_id = down.idDevice
                LEFT JOIN (
                    WITH FirstSendData AS (
                        SELECT
                            contact_id,
                            device_id,
                            CASE
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) <
                                    MIN(CASE WHEN fromMe = 'false' THEN
            created_at END) THEN 'sales'
                                WHEN MIN(CASE WHEN fromMe = 'false' THEN
            created_at END) <
                                    MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) THEN 'customer'
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) IS NOT NULL THEN 'sales'
                                ELSE 'customer'
                            END AS firstSendBy,
                            IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN
            fromMe = 'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END))<0,
                            TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
            'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END)) *-1,
                            TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
            'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END))) AS timeReply
                        FROM
                            chats
                        WHERE
                            created_at BETWEEN ? AND ?
                        GROUP BY
                            contact_id, device_id
                    )
                    SELECT
                        device_id,
                        COUNT(CASE WHEN firstSendBy = 'sales' THEN 1
            END) AS bySales,
                        COUNT(CASE WHEN firstSendBy = 'customer' THEN 1
            END) AS byCustomer,
                        SUM(timeReply) AS timeReply
                    FROM
                        FirstSendData
                    GROUP BY
                        device_id
                    ORDER BY
                        device_id ASC
                ) first_send ON first_send.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        device_id,
                        COUNT(*) AS totContacts
                    FROM contacts
                    GROUP BY device_id
                ) contact ON contact.device_id = down.idDevice
                WHERE down.idDevice IS NOT NULL

                UNION

                SELECT
                    down.idUpline,
                    down.idDevice,
                    down.name,
                    down.phone,
                    down.user_id,
                    down.user_name,
                    down.uuid,
                    send_reply.totSend,
                    send_reply.totReply,
                    first_send.byCustomer,
                    first_send.bySales,
                    first_send.timeReply,
                    contact.totContacts,
                    CASE
                        WHEN COALESCE(first_send.timeReply, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.timeReply, 0) /
            COALESCE(first_send.byCustomer+first_send.bySales, 0)) *
            100, 2)
                    END AS minuteReplyPercent,
                    CASE
                        WHEN COALESCE(contact.totContacts, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(send_reply.totSend, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendPercent,
                    CASE
                        WHEN COALESCE(send_reply.totSend, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(send_reply.totReply, 0) /
            COALESCE(send_reply.totSend, 0)) * 100, 2)
                    END AS replyPercent,
                    CASE
                        WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.byCustomer, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendFirstCustomerPercent,
                    CASE
                        WHEN COALESCE(first_send.bySales, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.bySales, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendFirstSalesPercent,
                    DATEDIFF(?, ?) + 1 AS totDays
                FROM
                    (SELECT
                        dw.user_id AS idUpline,
                        dv.id AS idDevice,
                        dv.name,
                        dv.phone,
                        dv.user_id,
                        dv.user_name,
                        dv.uuid
                    FROM devices dv
                    LEFT JOIN downlines dw ON dv.user_id =
            dw.downline_user_id AND dw.user_id = ?
                    WHERE
                        dv.status = 1
                        AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
                    ORDER BY dv.id DESC
                    ) down
                LEFT JOIN (
                    SELECT
                        ch1.device_id,
                        SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0
            END) AS totSend,
                        SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0
            END) AS totReply
                    FROM
                        (SELECT
                            device_id,contact_id,MIN(created_at),fromMe
                        FROM chats
                        WHERE
                            created_at BETWEEN ? AND ?
                        GROUP BY device_id,contact_id,fromMe) ch1
                    GROUP BY ch1.device_id
                ) send_reply ON send_reply.device_id = down.idDevice
                LEFT JOIN (
                    WITH FirstSendData AS (
                        SELECT
                            contact_id,
                            device_id,
                            CASE
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) <
                                    MIN(CASE WHEN fromMe = 'false' THEN
            created_at END) THEN 'sales'
                                WHEN MIN(CASE WHEN fromMe = 'false' THEN
            created_at END) <
                                    MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) THEN 'customer'
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) IS NOT NULL THEN 'sales'
                                ELSE 'customer'
                            END AS firstSendBy,
                            IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN
            fromMe = 'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END))<0,
                            TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
            'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END)) *-1,
                            TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
            'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END))) AS timeReply
                        FROM
                            chats
                        WHERE
                            created_at BETWEEN ? AND ?
                        GROUP BY
                            contact_id, device_id
                    )
                    SELECT
                        device_id,
                        COUNT(CASE WHEN firstSendBy = 'sales' THEN 1
            END) AS bySales,
                        COUNT(CASE WHEN firstSendBy = 'customer' THEN 1
            END) AS byCustomer,
                        SUM(timeReply) AS timeReply
                    FROM
                        FirstSendData
                    GROUP BY
                        device_id
                    ORDER BY
                        device_id ASC
                ) first_send ON first_send.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        device_id,
                        COUNT(*) AS totContacts
                    FROM contacts
                    GROUP BY device_id
                ) contact ON contact.device_id = down.idDevice
                WHERE down.user_id IS NULL AND down.idUpline IS NOT NULL

                UNION

                SELECT
                    down.idUpline,
                    down.idDevice,
                    down.name,
                    down.phone,
                    down.user_id,
                    down.user_name,
                    down.uuid,
                    send_reply.totSend,
                    send_reply.totReply,
                    first_send.byCustomer,
                    first_send.bySales,
                    first_send.timeReply,
                    contact.totContacts,
                    CASE
                        WHEN COALESCE(first_send.timeReply, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.timeReply, 0) /
            COALESCE(first_send.byCustomer+first_send.bySales, 0)) *
            100, 2)
                    END AS minuteReplyPercent,
                    CASE
                        WHEN COALESCE(contact.totContacts, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(send_reply.totSend, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendPercent,
                    CASE
                        WHEN COALESCE(send_reply.totSend, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(send_reply.totReply, 0) /
            COALESCE(send_reply.totSend, 0)) * 100, 2)
                    END AS replyPercent,
                    CASE
                        WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.byCustomer, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendFirstCustomerPercent,
                    CASE
                        WHEN COALESCE(first_send.bySales, 0) = 0 THEN
            0.00
                        ELSE ROUND((COALESCE(first_send.bySales, 0) /
            COALESCE(contact.totContacts, 0)) * 100, 2)
                    END AS sendFirstSalesPercent,
                    DATEDIFF(?, ?) + 1 AS totDays
                FROM
                    (SELECT
                        dw.user_id AS idUpline,
                        dv.id AS idDevice,
                        dv.name,
                        dv.phone,
                        dv.user_id,
                        dv.user_name,
                        dv.uuid
                    FROM devices dv
                    LEFT JOIN downlines dw ON dv.user_id =
            dw.downline_user_id AND dw.user_id = ?
                    WHERE
                        dv.status = 1
                        AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
                    ORDER BY dv.id DESC
                    ) down
                LEFT JOIN (
                    SELECT
                        ch1.device_id,
                        SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0
            END) AS totSend,
                        SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0
            END) AS totReply
                    FROM
                        (SELECT
                            device_id,contact_id,MIN(created_at),fromMe
                        FROM chats
                        WHERE
                            created_at BETWEEN ? AND ?
                        GROUP BY device_id,contact_id,fromMe) ch1
                    GROUP BY ch1.device_id
                ) send_reply ON send_reply.device_id = down.idDevice
                LEFT JOIN (
                    WITH FirstSendData AS (
                        SELECT
                            contact_id,
                            device_id,
                            CASE
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) <
                                    MIN(CASE WHEN fromMe = 'false' THEN
            created_at END) THEN 'sales'
                                WHEN MIN(CASE WHEN fromMe = 'false' THEN
            created_at END) <
                                    MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) THEN 'customer'
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN
            created_at END) IS NOT NULL THEN 'sales'
                                ELSE 'customer'
                            END AS firstSendBy,
                            IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN
            fromMe = 'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END))<0,
                            TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
            'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END)) *-1,
                            TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe =
            'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN
            created_at END))) AS timeReply
                        FROM
                            chats
                        WHERE
                            created_at BETWEEN ? AND ?
                        GROUP BY
                            contact_id, device_id
                    )
                    SELECT
                        device_id,
                        COUNT(CASE WHEN firstSendBy = 'sales' THEN 1
            END) AS bySales,
                        COUNT(CASE WHEN firstSendBy = 'customer' THEN 1
            END) AS byCustomer,
                        SUM(timeReply) AS timeReply
                    FROM
                        FirstSendData
                    GROUP BY
                        device_id
                    ORDER BY
                        device_id ASC
                ) first_send ON first_send.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        device_id,
                        COUNT(*) AS totContacts
                    FROM contacts
                    GROUP BY device_id
                ) contact ON contact.device_id = down.idDevice
                WHERE down.idUpline IS NULL
            )
            SELECT
                u.name as user,
                b.*,
                COUNT(*) OVER() as total_count
            FROM base_data b
            LEFT JOIN users u ON u.id = b.user_id
            
        ", [
            $endDate, $startDate,
            Auth::user()->id,
            $startDate, $endDate,
            $startDate, $endDate,
            $endDate, $startDate,
            Auth::user()->id, Auth::user()->id,
            $startDate, $endDate,
            $startDate, $endDate,
            $endDate, $startDate,
            Auth::user()->id, Auth::user()->id,
            $startDate, $endDate,
            $startDate, $endDate
        ]);
        // $reports = DB::select("
        //     WITH base_data AS (
        //         SELECT
        //             down.idUpline,
        //             down.idDevice,
        //             down.name,
        //             down.phone,
        //             down.user_id,
        //             down.user_name,
        //             down.uuid,
        //             send_reply.totSend,
        //             send_reply.totReply,
        //             first_send.byCustomer,
        //             first_send.bySales,
        //             first_send.timeReply,
        //             contact.totContacts,
        //             CASE
        //                 WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 0)) * 100, 2)
        //             END AS minuteReplyPercent,
        //             CASE
        //                 WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendPercent,
        //             CASE
        //                 WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 0)) * 100, 2)
        //             END AS replyPercent,
        //             CASE
        //                 WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendFirstCustomerPercent,
        //             CASE
        //                 WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendFirstSalesPercent,
        //             DATEDIFF(?, ?) + 1 AS totDays
        //         FROM
        //             (SELECT
        //                 dw.user_id AS idUpline,
        //                 dv.id AS idDevice,
        //                 dv.name,
        //                 dv.phone,
        //                 dv.user_id,
        //                 dv.user_name,
        //                 dv.uuid
        //             FROM downlines dw
        //             INNER JOIN devices dv ON dv.user_id = dw.downline_user_id
        //             WHERE
        //                 dw.user_id = ?
        //                 AND dv.status = 1
        //             ORDER BY dv.id DESC
        //             ) down
        //         LEFT JOIN (
        //             SELECT
        //                 ch1.user_id,
        //                 SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
        //                 SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
        //             FROM
        //                 (SELECT
        //                     user_id,contact_id,MIN(created_at),fromMe
        //                 FROM chats
        //                 WHERE            
        //                     created_at BETWEEN ? AND ?
        //                 GROUP BY user_id,contact_id,fromMe) ch1
        //             GROUP BY ch1.user_id
        //         ) send_reply ON send_reply.user_id = down.user_id
        //         LEFT JOIN (
        //             WITH FirstSendData AS (
        //                 SELECT
        //                     contact_id,
        //                     user_id,
        //                     CASE 
        //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) < 
        //                              MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
        //                         WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) < 
        //                              MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
        //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
        //                         ELSE 'customer'
        //                     END AS firstSendBy,
        //                     IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))<0,  
        //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END)) *-1,
        //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))) AS timeReply
        //                 FROM 
        //                     chats
        //                 WHERE            
        //                     created_at BETWEEN ? AND ?
        //                 GROUP BY 
        //                     contact_id, user_id
        //             )
        //             SELECT
        //                 user_id,
        //                 COUNT(CASE WHEN firstSendBy = 'sales' THEN 1 END) AS bySales,
        //                 COUNT(CASE WHEN firstSendBy = 'customer' THEN 1 END) AS byCustomer,
        //                 SUM(timeReply) AS timeReply
        //             FROM 
        //                 FirstSendData
        //             GROUP BY 
        //                 user_id
        //             ORDER BY 
        //                 user_id ASC
        //         ) first_send ON first_send.user_id = down.user_id
        //         LEFT JOIN (
        //             SELECT
        //                 user_id,
        //                 COUNT(*) AS totContacts
        //             FROM contacts
        //             GROUP BY user_id
        //         ) contact ON contact.user_id = down.user_id
        //         WHERE down.user_id IS NOT NULL

        //         UNION

        //         SELECT
        //             down.idUpline,
        //             down.idDevice,
        //             down.name,
        //             down.phone,
        //             down.user_id,
        //             down.user_name,
        //             down.uuid,
        //             send_reply.totSend,
        //             send_reply.totReply,
        //             first_send.byCustomer,
        //             first_send.bySales,
        //             first_send.timeReply,
        //             contact.totContacts,
        //             CASE
        //                 WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 0)) * 100, 2)
        //             END AS minuteReplyPercent,
        //             CASE
        //                 WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendPercent,
        //             CASE
        //                 WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 0)) * 100, 2)
        //             END AS replyPercent,
        //             CASE
        //                 WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendFirstCustomerPercent,
        //             CASE
        //                 WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendFirstSalesPercent,
        //             DATEDIFF(?, ?) + 1 AS totDays
        //         FROM
        //             (SELECT
        //                 dw.user_id AS idUpline,
        //                 dv.id AS idDevice,
        //                 dv.name,
        //                 dv.phone,
        //                 dv.user_id,
        //                 dv.user_name,
        //                 dv.uuid
        //             FROM devices dv
        //             LEFT JOIN downlines dw ON dv.user_id = dw.downline_user_id AND dw.user_id = ?
        //             WHERE
        //                 dv.status = 1
        //                 AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
        //             ORDER BY dv.id DESC
        //             ) down
        //         LEFT JOIN (
        //             SELECT
        //                 ch1.user_id,
        //                 SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
        //                 SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
        //             FROM
        //                 (SELECT
        //                     user_id,contact_id,MIN(created_at),fromMe
        //                 FROM chats
        //                 WHERE            
        //                     created_at BETWEEN ? AND ?
        //                 GROUP BY user_id,contact_id,fromMe) ch1
        //             GROUP BY ch1.user_id
        //         ) send_reply ON send_reply.user_id = down.idUpline
        //         LEFT JOIN (
        //             WITH FirstSendData AS (
        //                 SELECT
        //                     contact_id,
        //                     user_id,
        //                     CASE 
        //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) < 
        //                              MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
        //                         WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) < 
        //                              MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
        //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
        //                         ELSE 'customer'
        //                     END AS firstSendBy,
        //                     IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))<0,  
        //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END)) *-1,
        //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))) AS timeReply
        //                 FROM 
        //                     chats
        //                 WHERE            
        //                     created_at BETWEEN ? AND ?
        //                 GROUP BY 
        //                     contact_id, user_id
        //             )
        //             SELECT
        //                 user_id,
        //                 COUNT(CASE WHEN firstSendBy = 'sales' THEN 1 END) AS bySales,
        //                 COUNT(CASE WHEN firstSendBy = 'customer' THEN 1 END) AS byCustomer,
        //                 SUM(timeReply) AS timeReply
        //             FROM 
        //                 FirstSendData
        //             GROUP BY 
        //                 user_id
        //             ORDER BY 
        //                 user_id ASC
        //         ) first_send ON first_send.user_id = down.idUpline
        //         LEFT JOIN (
        //             SELECT
        //                 user_id,
        //                 COUNT(*) AS totContacts,phone
        //             FROM contacts
        //             GROUP BY user_id,phone
        //         ) contact ON contact.user_id = down.idUpline
        //         WHERE down.user_id IS NULL AND down.idUpline IS NOT NULL

        //         UNION

        //         SELECT
        //             down.idUpline,
        //             down.idDevice,
        //             down.name,
        //             down.phone,
        //             down.user_id,
        //             down.user_name,
        //             down.uuid,
        //             send_reply.totSend,
        //             send_reply.totReply,
        //             first_send.byCustomer,
        //             first_send.bySales,
        //             first_send.timeReply,
        //             contact.totContacts,
        //             CASE
        //                 WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 0)) * 100, 2)
        //             END AS minuteReplyPercent,
        //             CASE
        //                 WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendPercent,
        //             CASE
        //                 WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 0)) * 100, 2)
        //             END AS replyPercent,
        //             CASE
        //                 WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendFirstCustomerPercent,
        //             CASE
        //                 WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
        //                 ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 0)) * 100, 2)
        //             END AS sendFirstSalesPercent,
        //             DATEDIFF(?, ?) + 1 AS totDays
        //         FROM
        //             (SELECT
        //                 dw.user_id AS idUpline,
        //                 dv.id AS idDevice,
        //                 dv.name,
        //                 dv.phone,
        //                 dv.user_id,
        //                 dv.user_name,
        //                 dv.uuid
        //             FROM devices dv
        //             LEFT JOIN downlines dw ON dv.user_id = dw.downline_user_id AND dw.user_id = ?
        //             WHERE
        //                 dv.status = 1
        //                 AND (dv.user_id = ? OR dw.user_id IS NOT NULL)
        //             ORDER BY dv.id DESC
        //             ) down
        //         LEFT JOIN (
        //             SELECT
        //                 ch1.device_id,
        //                 SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
        //                 SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
        //             FROM
        //                 (SELECT
        //                     device_id,contact_id,MIN(created_at),fromMe
        //                 FROM chats
        //                 WHERE            
        //                     created_at BETWEEN ? AND ?
        //                 GROUP BY device_id,contact_id,fromMe) ch1
        //             GROUP BY ch1.device_id
        //         ) send_reply ON send_reply.device_id = down.idDevice
        //         LEFT JOIN (
        //             WITH FirstSendData AS (
        //                 SELECT
        //                     contact_id,
        //                     device_id,
        //                     CASE 
        //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) < 
        //                              MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
        //                         WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) < 
        //                              MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
        //                         WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
        //                         ELSE 'customer'
        //                     END AS firstSendBy,
        //                     IF (TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))<0,  
        //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END)) *-1,
        //                     TIMESTAMPDIFF(MINUTE, MIN(CASE WHEN fromMe = 'true' THEN created_at END), 
        //                     MIN(CASE WHEN fromMe = 'false' THEN created_at END))) AS timeReply
        //                 FROM 
        //                     chats
        //                 WHERE            
        //                     created_at BETWEEN ? AND ?
        //                 GROUP BY 
        //                     contact_id, device_id
        //             )
        //             SELECT
        //                 device_id,
        //                 COUNT(CASE WHEN firstSendBy = 'sales' THEN 1 END) AS bySales,
        //                 COUNT(CASE WHEN firstSendBy = 'customer' THEN 1 END) AS byCustomer,
        //                 SUM(timeReply) AS timeReply
        //             FROM 
        //                 FirstSendData
        //             GROUP BY 
        //                 device_id
        //             ORDER BY 
        //                 device_id ASC
        //         ) first_send ON first_send.device_id = down.idDevice
        //         LEFT JOIN (
        //             SELECT
        //                 device_id,
        //                 COUNT(*) AS totContacts
        //             FROM contacts
        //             GROUP BY device_id
        //         ) contact ON contact.device_id = down.idDevice
        //         WHERE down.idUpline IS NULL
        //     )
        //     SELECT 
        //         u.name as user,
        //         b.*
        //     FROM base_data b
        //     LEFT JOIN users u ON u.id = b.user_id
        // ", [
        //     $endDate, $startDate,
        //     Auth::user()->id,
        //     $startDate, $endDate,
        //     $startDate, $endDate,
        //     $endDate, $startDate,
        //     Auth::user()->id, Auth::user()->id,
        //     $startDate, $endDate,
        //     $startDate, $endDate,
        //     $endDate, $startDate,
        //     Auth::user()->id, Auth::user()->id,
        //     $startDate, $endDate,
        //     $startDate, $endDate
        // ]);

        $filename = 'reports_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, [
                'Account/Downline Name',
                'Device Name',
                'Phone',
                'Total Days',
                'Total Contacts',
                'Total Send',
                'Send Rate (%)',
                'Total Sales Initiated Send',
                'Sales Initiated Send Rate (%)',
                'Total Customer Initiated Send',
                'Customer Initiated Send Rate (%)',
                'Total Reply',
                'Reply Rate (%)',
                'Average Reply Time'
            ]);

            // Add data
            foreach ($reports as $report) {
                $time = \Carbon\Carbon::createFromTimestampUTC(($report->minuteReplyPercent / 100) * 60);
                fputcsv($file, [
                    $report->user,
                    $report->name,
                    $report->phone,
                    $report->totDays,
                    $report->totContacts,
                    $report->totSend,
                    $report->sendPercent,
                    $report->bySales,
                    $report->sendFirstSalesPercent,
                    $report->byCustomer,
                    $report->sendFirstCustomerPercent,
                    $report->totReply,
                    $report->replyPercent,
                    $time->hour . 'h ' . $time->minute . 'm ' . $time->second . 's'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}