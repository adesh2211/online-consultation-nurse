 require('dotenv').config({path:'../.env'});
  var app = require('express')();
  var server = require('http').Server(app);
  var io = require('socket.io')(server);
  var mysql = require('mysql');
  var Redis = require('ioredis');
  var moment = require('moment');
  var FCM = require('fcm-node');
  var serverKey = process.env.SERVER_KEY_ANDRIOD;
  var fcm = new FCM(serverKey);
  var redisClient = Redis.createClient({ host: process.env.REDIS_HOST, port: process.env.REDIS_PORT,db:'0' });
  var db_config = {
      host: process.env.DB_HOST,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE,
    port:process.env.DB_PORT
      };
  var con;
  var connected_user_socket_ids=[];
  var port_number = process.env.SOCKET_IO_PORT || 8080;
  server.listen(port_number, function(){
      console.log("Listening on localhost:"+port_number)
  });
  ///////////////////////////////////////         App Chat    //////////////////////////////////////////////////////////
  redisClient.subscribe('message');
  redisClient.subscribe('laravel_database_message');

  redisClient.on("message", function(channel, data) {
    var messageData = JSON.parse(data);
    connected_user_socket_ids.forEach(function(id){
      try{
          console.log('sending to socket - '+id);
            io.sockets.connected[id].emit('message',messageData);
        }catch(e){
          console.log('Socket Emit Error - '+e);
        }
    });
    io.emit(channel, data);
  });
  redisClient.on("error", function(error) {
    console.error(error);
  })
  io.on('connect', function (socket) {
     var handshake = socket.handshake;
     var db_name = process.env.DB_DATABASE;
     if(handshake.query!==undefined && handshake.query.domain!==undefined){
        db_name = 'db_'+handshake.query.domain;
     }
    var fcm  = null;
     if(db_name=='db_curenik'){
        fcm = new FCM("AAAAJAP2EBA:APA91bHw_aB6CIomfP02M5Q3cCeScWBj9k3sMI4asx4qmrtJorX3hyCB_OofwWFxd6cK3LhDNr03e5cul-rbEFxUmgWY2FEyCxpEASR_nEGqjBzHL7OmMuvoQ46N4qfvt0iC6SMChHny");
     }else if(db_name=='db_physiotherapist'){
        fcm = new FCM("AAAAfMsLRAc:APA91bHqtrp0Q9Aiy8DNBnCZHAIoSmPzLBYsjxsCy5UYifIP1sukPUYbQEnAfwVBXXr1LLs2p2eOwXOyHlIRWLo4Oil51QCzwBfxqKxrATQO2qz3O31F8OqGiBADJKd4Sa-SklH2fxr5");
     }else if(db_name=='db_healthcare'){
        fcm = new FCM("AAAAP6q3OX8:APA91bGmKnI1R9iYL-Cc9grwEJ0rZekgz3N4lGuefZcU2RAxiKk4dRqolfAOkgeVVnxlYkRJObKFgNWnz3HQncgcDhg_Pi0zxYqv3CmV5STxJ03aLvX3QZJNvI11SiCsTayZusy45AhO");
     }else if(db_name=='db_healtcaremydoctor'){
        fcm = new FCM("AAAA34cddfc:APA91bEEa_k2wElR68Nn7DDMB32o1P-1d-qvMA_S_WKDj9qL49YDn70enxibcNvOzJcD15OokDFcZJSE1ew_AuMUZSL40BS8UQd9Uh6Ztk4NrsY4upR3FpvxvqWNigI5goKyspjHZIbL");
     }else if(db_name=='db_education'){
        fcm = new FCM("AAAA5XQpJX8:APA91bGAoctJ4pF3OJQG47VeAvdBFfuiHdZj7jqtycinW04yxJo_2Hj-o4hi4LIiYICvsG19Dn-_UA439PDBirY6Z-cdYlByoxGHQ9W9xWNx_MzbiDZ5dK2XVWv7O6_qOAbDvbpwAiqW");
     }else if(db_name=='db_marketplace'){
        fcm = new FCM("AAAA3XOvb-8:APA91bHHPgvNts5elTPiPFn19kYPU-jvhoQv_P_gx-dD5czvKyB2wEdY3XmGDn-3u5VBVbFPbAv0aHJI0xioN5EqCfLcuUnM7rU62N9x8Ng-F6RS5vBfaHqpc4GYeS8Naa1eGQAh7Q3d");
     }else if(db_name=='db_default'){
        fcm = new FCM("AAAA3cw_ZOY:APA91bEhM5Y7VMATmlOkYxae_5KOwcS4FAiJ-FTZy01xtXxzbcxG1vtFp-dEOy3CSmriYR8Jz2avw3SnEwDdhoCd4zVrJQu_kRIctEv-kcP6faGpGgtI5vE2gbNBt-wwXSAgTmHSFrzX");
     }else if(db_name=='db_heal'){
        fcm = new FCM("AAAA6TV5n_k:APA91bEFAAQn-yv_eoFkqRWW85x3xK0S1fBky_Vi51FCghV7Arn5dC8S45ElyVrX7Nw3fm3pJ-axQ2Ths3boLRnUdn53MvTFgDUtKQe6m1U9G9Xc81GyDp1qpKJC9mDF4UqsHfX74q08");
     }else if(db_name=='db_homedoctor'){
        fcm = new FCM("AAAANM0FXDE:APA91bHzRNwliVNXjAoyfqssBn3SaU-wxnntyCaNi9kTtfnc7FuIaBZfPw6UNVhK7TK9Mm-p2j_1B4n4f0hG5FzcdKr2c86Gjs8oqOlqnevbfd-45BOR0eCzrwubmq0CoKTU1haB6QvS");
     }else if(db_name=='db_meetmd'){
        fcm = new FCM("AAAA5zzxf7A:APA91bGpPD6CZEwwO9XdDDrebLYhCWkyGMGXWl7X50u3HVjwlzoUklJ5R8TOEHSiZ62EhxbW-k9UiSfnVwmCRGJzn8F9myu2WnSiGCBus4v_V1X6L31W6YmsmkGfLCvZxcOXUwQJ5vg8");
     }else if(db_name=='db_airdoc'){
        fcm = new FCM("AAAA38hjuQs:APA91bGYEl4yefDaRpe0yv9w4Pc7HYrSZL413WW0pJB5-YqkwCKr3zNFvTvm3xZI4LlJMwDor6XJcl5Wn8ctwaY62_vSvwlCrFVrHhL2el-h4Z1tZEmSQ1KiacR8cgV944thVNl8X2Co");
     }else if(db_name=='db_iedu'){
        fcm = new FCM("AAAAtvZTfBY:APA91bErBdLuYyjqvKjJzc3ThLbQLwcluCCCUV18mMSdP0DisFa3F5mAxdBZHgI06ipkc5XSkeL-lbsXJ5zGB8C1R4Z9_JkK2Seeq4qCTeU3VzpMuEUatHkCKDgbzhtxppBUps1EOtgG");
     }else if(db_name=='db_taradoc'){
        fcm = new FCM("AAAAGuScgqs:APA91bH_b_K5N7efge8dmA-pwsCbmxLiAcZOXHHQrNnkc_VT2OPtw3JXz2DJzj0n6J5qoVpzEIaqdV1WqYTUI9k1kI5LK85UEIdAA4u2fn10p_JdOt4mXp39d9foPgZ6jHmHcOAv3CG_");
     }else if(db_name=='db_care_connect_live'){
        fcm = new FCM("AAAAPNUBkt8:APA91bEIBN0ToJ5Rkq1PKHOBu9h7YoKQNIcJMhahFvd7rmuhU4EaKiVh_-Lcx8u6clk057G-YA3Zw7vcbs61gk87gFvlvsIB3qGf3XGh3Q238gvXsST9DPOvpVo3uIoI3z8WuqZsQZRm");
     }else if(db_name=='db_clouddoc'){
        fcm = new FCM("AAAAfuO3mJo:APA91bEWlsEv80Wi5oeTMc4xg9eA9X6JKf6yZD1rTwHJvtHDZd2w4sO8bcNUev73_1mDEr7KA-6A6cIYdiBIgHf8INI39tRDimzYD399whE0dawW5bEuXpFE0T_IlcDagkhHBmEDRYZ9");
     }else{
        fcm = new FCM("AAAA3cw_ZOY:APA91bEhM5Y7VMATmlOkYxae_5KOwcS4FAiJ-FTZy01xtXxzbcxG1vtFp-dEOy3CSmriYR8Jz2avw3SnEwDdhoCd4zVrJQu_kRIctEv-kcP6faGpGgtI5vE2gbNBt-wwXSAgTmHSFrzX");
     }
     var con;
     var db_config = {
        host: process.env.DB_HOST,
        user: process.env.DB_USERNAME,
        password: process.env.DB_PASSWORD,
        database: db_name,
        port:process.env.DB_PORT
      };
      console.log('db_name',db_name);
      console.log('connect fcm first ',fcm.serverKey);
      function handleDisconnect() {
        con = mysql.createConnection(db_config); // Recreate the connection, since
        con.connect(function(err) {              // The server is either down
            if(err){                                     // or restarting (takes a while sometimes).
                console.log('error when connecting to db:', err.message);
                setTimeout(handleDisconnect, 2000); // We introduce a delay before attempting to reconnect,
              }
            else{
                   
                    var user_id = undefined;
                    if(handshake.query!==undefined && handshake.query.user_id!==undefined){
                      user_id = handshake.query.user_id;
                      var sessionID = socket.id;
                      if(user_id==undefined)
                          console.log('user_id is Required');
                        else{
                            var user = con.query("SELECT * FROM users WHERE id= '"+user_id+"'", function(error, results, fields){
                            if(error){
                                console.log(error.message);
                              }else if(results == undefined || results.length == 0){
                                console.log("No User found");
                              }else{
                                console.log('user_id sessionID',user_id,sessionID);
                                var update_device = con.query("UPDATE users SET socket_id='"+sessionID+"' WHERE id="+results[0].id+" ", function(ierror, iresults, ifields){
                                    if(ierror){
                                        console.log(ierror.message);
                                      }else{
                                        io.sockets.connected[sessionID].emit('success',{'message':'connected'});
                                      }

                                });
                              }
                            });
                          }
                    }
                    socket.on('readMessage',function(data,callback){
                      console.log('readMessage',data);
                      if(data.messageId){
                        con.query("UPDATE messages SET `status`='SEEN' WHERE id="+data.messageId+" AND `status`='DELIVERED'", function(ierror, iresults, ifields){
                          con.query("SELECT * FROM users WHERE id= '"+data.receiverId+"'", function(error, results, fields){
                            if(results !== undefined && results.length !== 0){
                              try{
                                io.sockets.connected[results[0].socket_id].emit('readMessage',data);
                              }catch(e){
                                  console.log('Socket Emit Error - '+e);
                                  callback({'status':'ERROR','message':e});
                              }
                            }
                            if(ierror){
                              callback({'status':'ERROR','message':ierror.message});
                            }else if(iresults == undefined || iresults.length == 0){
                              callback({'status':'ALREADY_SEEN'});
                            }else{
                              callback({'status':'SEEN'});
                            }
                          });
                        });
                      }
                    });
                    socket.on("typing", function(data) {
                        if(data && data.receiverId){
                            con.query("SELECT * FROM users WHERE id= '"+data.receiverId+"'", function(error, results, fields){
                              if(results !== undefined && results.length !== 0){
                                try{
                                  io.sockets.connected[results[0].socket_id].emit('typing',data);
                                }catch(e){
                                    console.log('Socket Emit Error - '+e);
                                }
                              }
                            });
                        }
                    });
                    socket.on('deliveredMessage',function(data,callback){
                      console.log('deliveredMessage',data);
                      if(data.messageId){
                        con.query("UPDATE messages SET `status`='DELIVERED' WHERE id="+data.messageId+" AND `status`='SENT'", function(ierror, iresults, ifields){
                          con.query("SELECT * FROM users WHERE id= '"+data.receiverId+"'", function(error, results, fields){
                              if(results !== undefined && results.length !== 0){
                                try{
                                  io.sockets.connected[results[0].socket_id].emit('deliveredMessage',data);
                                }catch(e){
                                  console.log('Socket Emit Error - '+e);
                                  callback({'status':'ERROR','message':e});
                                }
                              }
                              if(ierror){
                                callback({'status':'ERROR','message':ierror.message});
                              }else if(iresults == undefined || iresults.length == 0){
                                callback({'status':'ALREADY_DELIVERED'});
                              }else{
                                callback({'status':'DELIVERED'});
                              }
                          });
                        });
                      }
                    });
                    socket.on('sendlivelocation', async (data,callback) => {
                      let locationData = {};
                      let senderId = '';
                      var tempdata = data;
                      senderId = tempdata.senderId;
                      if(tempdata.request_id){
                          con.query("INSERT INTO last_locations(`request_id`,`user_id`, `lat`, `long`) VALUES(?,?,?,?)", [tempdata.request_id,tempdata.senderId, tempdata.lat, tempdata.long], function (err, result) {
                                if (err) {
                                  callback({'status':'ERROR','message':err});
                                } else {
                                    con.query("SELECT * FROM users WHERE id= '"+data.receiverId+"'", function(error, results, fields){
                                      message = {
                                          'message': 'location updated successfully',
                                          'success': 1
                                      };
                                      locationData.senderId = tempdata.senderId;
                                      locationData.lat = tempdata.lat;
                                      locationData.request_id = tempdata.request_id;
                                      locationData.long = tempdata.long;
                                      locationData.receiverId = tempdata.receiverId;
                                      locationData.created_at = new Date();
                                      try{
                                        io.sockets.connected[results[0].socket_id].emit('sendlivelocation',locationData);
                                        console.log('sent........sendlivelocation');
                                        callback({'status':'success','message':'success'});
                                      }catch(e){
                                        console.log('Socket Emit Error - '+e);
                                        callback({'status':'ERROR','message':e});
                                      }
                                    });
                                }
                            });
                      }
                    });
                    socket.on("callVideo", function(data) {
                      if(data && data.callreceiverId){
                      con.query("SELECT * FROM users WHERE id= '"+data.callreceiverId+"'", function(error, results, fields){
                      if(results !== undefined && results.length !== 0){
                      try{
                      io.sockets.connected[results[0].socket_id].emit('incomingCall',data);
                      }catch(e){
                      console.log('Socket Emit Error - '+e);
                      }
                      }
                      });
                      }
                      });
                    socket.on('sendMessage', function(data,callback){
                      console.log('message',JSON.stringify(data));
                      console.log('fcm',fcm.serverKey);
                      console.log('db_name',db_name);
                      var ret_response = {};
                      data.pushType = 'chat';
                     // data.aps = {"content-available" : 1,"alert":{title:"chat",subtitle:data.message,body:""}}
                      data.dbImageUrl = data.imageUrl;
                      if(data.imageUrl==''){
                        data.imageUrl = null;
                      }
                      if(data){
                        con.query("SELECT * FROM users WHERE id= '"+data.receiverId+"'", function(error, results, fields){
                        if(error){
                            ret_response = {"status":"error","message":error.message};
                            callback(ret_response);
                          }else if(results == undefined || results.length == 0){
                            console.log("No User found");
                            ret_response = {"status":"error","message":"user not found"};
                            callback(ret_response);
                          }else{
                            con.query("SELECT * FROM request_history WHERE request_id= '"+data.request_id+"' AND status='in-progress'", function(error, requestResult, requestResultfields){
                              if(error){
                                console.log(error.message);
                                ret_response = {"status":"error","message":error.message};
                                callback(ret_response);
                              }
                              else if(requestResult==undefined || requestResult.length==0){
                                console.log('request completed or not found');
                                ret_response = {"status":"REQUEST_COMPLETED","message":"request completed or not found"};
                                callback(ret_response);
                              }
                              else{
                                  try{
                                    var dateString = moment.utc(data.sentAt).format("YYYY-MM-DD HH:mm:ss");
                                  }catch(e){
                                    var dateString = moment.utc().format("YYYY-MM-DD HH:mm:ss");
                                  }
                                  var message = { //this may vary according to the message type (single recipient, multicast, topic, et cetera)
                                      to: results[0].fcm_id,
                                      data:data,
                                      notification: {
                                          "title" : data.pushType,
                                          "body": data.message,
                                          "sound": "default",
                                          "badge": 0
                                      },
                                      priority: "high"
                                  };
                                var notification = null;
                                  if(results[0].device_type=='IOS'){
                                      notification = {
                                          "title" : data.pushType,
                                          "body": data.message,
                                          "sound": "default",
                                          "badge": 0
                                      };
                                  }
                                  var message = { //this may vary according to the message type (single recipient, multicast, topic, et cetera)
                                      to: results[0].fcm_id,
                                      data:data,
                                      notification:notification,
                                      priority: "high"
                                  };
                                  console.log('message',message);
                                  fcm.send(message, function(err, response){
                                      if (err) {
                                          console.log("FCM error ",err);
                                      } else {
                                          console.log("FCM Successfully sent with response: ", response);
                                      }
                                  });
                                //  fcm.send(message, function(err, response){
                                //      if (err) {
                                 //         console.log(err);
                                   //   } else {
                                     //     console.log("Successfully sent with response: ", response);
                                     // }
                                  //});
                                  var sql = "INSERT INTO messages (user_id,receiver_id, request_id,message,created_at,updated_at,image_url,message_type) VALUES ('"+data.senderId+"', '"+data.receiverId+"','"+data.request_id+"','"+data.message+"','"+dateString+"','"+dateString+"','"+data.dbImageUrl+"','"+data.messageType+"')";
                                  con.query(sql, function (err, result) {
                                      if (err){
                                        console.log("no record inserted",err.message);
                                      }else{
                                        try{
                                            data.messageId = result.insertId;
                                            console.log('sending to socket - '+results[0].socket_id);
                                            io.sockets.connected[results[0].socket_id].emit('messageFromServer',data);
                                            ret_response = {"status":"MESSAGE_SENT","message":"Message Sent","messageId":data.messageId};
                                            callback(ret_response);
                                          }catch(e){
                                            data.messageId = result.insertId;
                                            console.log('Socket Emit Error - '+e);
                                            ret_response = {"status":"MESSAGE_SENT","message":"Message Not Sent Client not connected","messageId":data.messageId};
                                            callback(ret_response);
                                          }
                                      }
                                    });
                                }  

                              });
                            }
                        });
                      }else{
                        console.log('No data found');
                        ret_response = {"status":"error","message":"No data found"};
                        callback(ret_response);
                      }
                    });
                    socket.on('disconnect', function(error,data){
                          console.log('error',error);
                    });
              }
        })
        con.on('error', function(err) {     
            console.log('db error', err);
            if(err.code === 'PROTOCOL_CONNECTION_LOST'){ 
            // Connection to the MySQL server is usually
                  handleDisconnect(); 
              }
            else{
                  // connnection idle timeout (the wait_timeout
                  console.log("Mysql Error - ",err.message);
                  throw err;
              }
        });
      }
      handleDisconnect();
    
  });
