# Hướng dẫn gửi SMS

## 1.Mô tả trình tự thực hiện

1. Đối tác tạo cặp khóa bất đối xứng bằng thuật toán RSA, gửi `Public Key` cho SAMI để SAMI import vào hệ thống

    Ví dụ sau tạo `Private Key` sử dụng openssl

    ```bash
    openssl genrsa -out your.company.key 2048
    ```

    Sinh `Public Key` từ `Private Key` để gửi cho SAMI-S

    ```bash
    openssl rsa -in your.company.key -pubout -out your.company.public.key
    ```

2. SAMI sẽ import `Public Key` của đối tác vào hệ thống và cấp tài khoản truy cập API

3. Đối tác dùng `Private Key` để ký vào transaction và gửi qua API

4. SAMI sẽ sử dụng khóa `Public Key` của đối tác verify dữ liệu sau đó thực hiện các thủ tục gửi tin nhắn

Những điểm lưu ý với các tham số:

1. `TransactionID` luôn luôn duy nhất mỗi lần gọi, hệ thống sẽ dùng `TransactionID` để chống lặp transaction

2. `cooperateMsgId` luôn luôn duy nhất mỗi lần gọi, hệ thống sẽ dùng `cooperateMsgId` để chống lặp tin

3. Hệ thống nhận được Transaction Request sẽ so sánh thời gian gửi `CreateTime` với thời gian hệ thống, nếu quá 5 phút, hệ thống sẽ không cho phép gửi Transaction này

Bảng mô tả các quy tắc gửi SMS

| Value | Description |
|-----------|-----------------------------------------------------------------------------------|
| 100 | Số lần gọi sai cho phép, nếu lớn hơn giá trị này sẽ bị chặn |
| 5 | Thời gian cho phép gọi service sau khi bị chặn SPAM (tính bằng phút) |
| 300 | Số lượng tin nhắn được phép gửi trong 1 ngày tới 1 thuê bao |
| 150 | Số lượng tin nhắn được phép gửi trong 1 giờ tới 1 thuê bao |
| 25 | Số lượng tin nhắn được phép gửi trong 1 phút tới 1 thuê bao |
| 1000 | Số lượng tin nhắn được phép gửi trong 1 tháng tới 1 thuê bao |
| 1000 | Độ dài tin nhắn tối đa được phép (tính bằng ký tự) |
| 300 | Thời gian trễ được phép của Transaction so với hệ thống (tính bằng giây) |


Mã lỗi trả về trong report

| ErrorCode | ErrorDescription |
|-----------|-----------------------------------------------------------------------------------|
| 0 | Tin nhắn hợp lệ |
| 1 | CooperateMsgId đã tồn tại. |
| 2 | Gửi tin tới 1 số thuê bao vi phạm luật Spam (tra cứu bảng trên) |
| 3 | Không xác định được nhà mạng (TELCO) cho số điện thoại |
| 4 | Đối tác không được cấp quyền sử dụng ShortCode |
| 5 | Số điện thoại khách hàng nằm trong danh sách từ chối dịch vụ |
| 6 | Đối tác không được gửi MT chủ động khi không có MO trên ShortCode |
| 7 | Đối tác gửi Message trên ShortCode không giống mẫu đăng ký (Áp dụng trong trường hợp phải tuân theo mẫu đăng ký) |
| 8 | Đối tác đang ở chế độ thử nghiệm, chỉ được gửi tới Số điện thoại trong danh sách đăng ký. |


## 2.Hướng dẫn chi tiết SendSMS và nhận report

### 2.1 Gửi nhiều tin với nội dung khác nhau

#### Mô tả SMS TRANSACTION JSON OBJECT

```json
{
    "$id": "https://sami.com.vn/sms-transaction-json-schema.json",
    "$schema": "http://json-schema.org/draft-04/schema",
    "title": "JSON Schema for SmsTransaction format",
    "type": "object",
  "required": [ "transactionId", "coopereateId", "smsOuts", "createTime" ],
    "properties": {
        "transactionId": {
            "type": "string",
            "description": "TransactionId send sms"
        },
        "coopereateId": {
            "type": "integer",
            "description": "CooperateID"
        },
        "smsOuts": {
            "type": "array",
            "description": "An array of sms.",
            "minItems": 1,
            "items": { "$ref": "#/definitions/smsOutItem" }
        },
        "createTime":  {
            "format": "date-time",
            "type": "string",
            "description": "Event sending time",
        }
    },
    "definitions": {
        "smsOutItem": {
            "type": "object",
            "required": [ "CooperateMsgId", "DestAddr", "Message", "ShortCode" ],
            "properties": {
                "cooperateMsgId": {
                    "type": "string",
                    "description": "Mã định danh tin nhắn gửi từ phía đối tác"
                },
                "destAddr": {
                    "type": "string",
                    "description": "Số điện thoại gửi"
                },
                "message" : {
                    "type": "string",
                    "description": "Thông điệp gửi cho DesAddr"
                },
                "shortCode" : {
                    "type": "string",
                    "description": "Brandname hoặc đầu số gửi tin"
                },
                "mtType" : {
                    "type": "string",
                    "description": "Loại tin nhắn gửi"
                },   
            }
        }
    }
}
```

#### Mô tả TransactionRequest
```json
{
    "$id": "https://sami.com.vn/sms-transaction-request-json-schema.json",
    "$schema": "http://json-schema.org/draft-04/schema",
    "title": "JSON Schema for SmsTransaction Request format",
    "type": "object",
    "required": [ "Payload", "Signature"],
    "properties": {
        "payload": {
            "type": "string",
            "description": "Payload of transaction sms Base64 Encode"
        },
        "signature": {
            "type": "string",
            "description": "Signature for Payload Base64 Enocde"
        }
    }
}
```

#### Các bước thực hiện 

1. Tạo SMS TRANSACTION JSON OBJECT `jsonTran`

```json
{
    "transactionId": "uniqueID",
    "coopereateId" : 1,
    "smsOuts" : [
        {
            "cooperateMsgId": "Message Id of Cooperate",
            "destAddr" : "0912xxxxxx",
            "message" : "Message want to send to DestAddr",
            "shortCode": "Brandname or ShortCode",
            "mtType" : "AN",
            "createTime": "2019-10-10T11:37:27.328714+07:00"
        }
        // Các bản tin tiếp theo ...
    ]
}
```

2. Chuyển đổi sang mảng Bytes Array với encoding là UTF-8

3. Dùng khóa Private ký vào `byteData` được `byteSignature`

4. Đóng gói `byteData` thành Base64 và đưa vào `payload`

5. Đóng gói `byteSignature` thành Base64 và đưa vào `signature`

6. Tạo JSON Transaction Request

```json
{
    "payload": "Base64 Encoding",
    "signature": "Base64 Encoding",
}
```

#### Cài đặt thuật toán cho C#

```c#
// Đây chỉ là ví dụ minh họa
// Đối với C# nên sử dụng Newtonsoft.Json để SerializeObject và DeserializeObject

// Lấy token
string resToken;

using (var client = new HttpClient
    {
        BaseAddress = new Uri("https://auth.example.vn:8443/api/authenticate/token");
    })
{
    client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

    string authorizationBasic = Convert.ToBase64String(Encoding.UTF8.GetBytes(username + ":" + password));

    // Lấy JsonWebToken
    // Authorization header
    client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Basic", authorizationBasic);
    // x-client-id header
    client.DefaultRequestHeaders.Add("x-client-id", "efa66179-1eb9-4187-9c0f-52fc99388492");

    var result = client.PostAsync(TokenApiPath, null).Result;

    Console.WriteLine("====================================");
    Console.WriteLine($"TOKEN Http POST... {TokenApiPath}");
    Console.WriteLine($"Authorization Basic {authorizationBasic}");
    var resBytes = await result.Content.ReadAsByteArrayAsync();

    var resString = Encoding.UTF8.GetString(resBytes);

    if (result.StatusCode == System.Net.HttpStatusCode.OK)
    {
        // Loại bỏ dấu " vì kết quả trả về là một chuỗi json string dạng "token..."
        resToken = resString.Replace("\"", "");
        Console.WriteLine("====================================");
        Console.WriteLine($"TOKEN Http Response code <{result.StatusCode}>");
        Console.WriteLine($"TOKEN {resToken}");
        Console.WriteLine("====================================");
    }
    else
    {
        Console.WriteLine("====================================");
        Console.WriteLine($"TOKEN Http Response code <{result.StatusCode}>");
        Console.WriteLine($"Content {resString}");
        Console.WriteLine("====================================");
    }
}

// Chuẩn bị transaction gửi SMS
string jsonTransaction = @"{
            'transactionId': 'uniqueID',
            'coopereateId' : 1,
            'smsOuts' : [
                {
                    'cooperateMsgId': 'Id of message',
                    'destAddr' : '0912xxxxxx',
                    'message' : 'Message want to send to DestAddr',
                    'shortCode': 'Brandname or ShortCode',
                    'mtType' : 'AN',
                    'createTime': '2019 -10-10T11:37:27.328714+07:00'
                }
                // Các bản tin tiếp theo ...
            ]
        }";

// Convert jsonTransaction sang dạng Bytes định dạng đầu vào là UTF-8
byte[] byteData = Encoding.UTF8.GetBytes(jsonTransaction);

// ký vào byteData (Lưu ý, dữ liệu sẽ được Hash trước khi ký để giảm thời gian verify)
// Sử dụng thuật toán SHA256 để hash
byte[] byteSignature = Crypto.RsaCrypto.SignHash(byteData, provider);

// Chuyển đổi byteData thành Base64 để đưa vào payload
string payload = Convert.ToBase64String(byteData);

// Chuyển đổi byteSignature thành Base64 đưa vào signature
string signature = Convert.ToBase64String(byteSignature);

// Tạo TransactionRequest
SmsTransactionRequest smsTransactionRequest = new SmsTransactionRequest
{
    Payload = payload,
    Signature = signature
};

byte[] rawBytes = Encoding.UTF8.GetBytes(JsonConvert.SerializeObject(smsTransactionRequest));

try {

    var client = new HttpClient();

    client.BaseAddress = new Uri("https://auth.example.com:8558/");

    client.DefaultRequestHeaders.Accept.Add(
        new MediaTypeWithQualityHeaderValue("application/json"));

    // Add Header Bearer token
    client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", resToken);

    var byteArrayContent = new ByteArrayContent(rawBytes);

    byteArrayContent.Headers.ContentType = new MediaTypeHeaderValue("application/json");

    var result = client.PostAsync("api/sms/send", byteArrayContent).Result;

    var resBytes = await result.Content.ReadAsByteArrayAsync();

    resString = Encoding.UTF8.GetString(resBytes);

    // Kết quả thành công sẽ trả về json object
    if (result.StatusCode == System.Net.HttpStatusCode.OK)
    {
        // Chuyển đổi json object thành TransactionResponse để xử lý nghiệp vụ nội bộ tiếp theo
        var transResponse = JsonConvert.DeserializeObject<SmsTransactionResponse>(resString);
    } 
    
    // In kết quả ra màn hình (dù thành công hay không)
    Console.WriteLine(JsonConvert.SerializeObject(res));
    

    client.Dispose();

}
catch (Exception ex)
{
    Console.WriteLine(ex);
}

```

#### Response trả về

1. Với một request thành công, api sẽ trả về nội dung Response như sau

HTTP Status Code: 200 OK
Json Body
```json
{
    "transactionId": "uniqueID",
    "responseTime" : "2019-10-10T13:23:19.3949519+07:00",
    "smsReports" : [
        {
            "responeId": "346d9fc6-10d3-4561-b19a-f4ec1776f2ae",
            "cooperateMsgId": "1",
            "statusCode": 0,
            "statusMessage": null,
            "receivedTime": "2019-10-10T13:23:19.3949519+07:00"
        }
        // Các bản tin tiếp theo ...
    ]
}
```

2. Một request không thành công, api sẽ trả nội dung Response như sau:

HTTP Status: 4xx hoặc 5xx

Json Body
```json
{
    "ErrorCode": errorCode,
    "ErrorMessage": "Mô tả lỗi"
}
```


#### Những điểm lưu ý:

1. Nếu mã hóa không đúng, IP không được phép gửi, hoặc gửi Short Code không được cung cấp, hệ thống sẽ banned IP trong vòng 20 phút

2. Chỉ dùng JSON để trao đổi

### 2.2 Get Report

Request:

Method GET:

`api/sms/report/{id}`

Kết quả trả về
```json
{
    "cooperateMsgId": "1",
    "responeId": "346d9fc6-10d3-4561-b19a-f4ec1776f2ae",
    "status" : "SENT",
    "sentTime": "2019-10-10T13:23:19.3949519+07:00",
    "responseTime" : "2019-10-10T13:23:19.3949519+07:00",
}
```
