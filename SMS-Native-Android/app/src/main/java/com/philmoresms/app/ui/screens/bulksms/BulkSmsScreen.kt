package com.philmoresms.app.ui.screens.bulksms

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.*
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.components.LabeledDropdown
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class BulkSmsViewModel : ViewModel() {
    var senderIds by mutableStateOf<List<SenderId>>(emptyList())
    var corporateSenderIds by mutableStateOf<List<CorporateSenderId>>(emptyList())
    var smsConfig by mutableStateOf<SmsConfigResponse?>(null)
    var loading by mutableStateOf(false)

    var senderId by mutableStateOf("")
    var corporateSenderId by mutableStateOf("")
    var recipients by mutableStateOf("")
    var message by mutableStateOf("")
    var route by mutableStateOf("promotional")
    var sending by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    init { load() }

    fun load() {
        loading = true
        viewModelScope.launch {
            try {
                val list = RetrofitClient.apiService.getSenderIds().await().senderIds ?: emptyList()
                senderIds = list.filter { (it.type ?: "sms") == "sms" && it.status?.lowercase() == "approved" }
                val corp = RetrofitClient.apiService.getCorporateSenderIds().await().corporateSenderIds ?: emptyList()
                corporateSenderIds = corp.filter { it.status?.lowercase() == "approved" }
                smsConfig = RetrofitClient.apiService.getSmsConfig().await()
                if (senderIds.isNotEmpty() && senderId.isEmpty()) senderId = senderIds.first().senderId ?: ""
            } catch (e: Exception) {
                error = "Failed to load sender IDs / settings"
            } finally {
                loading = false
            }
        }
    }

    private val chars1Unit: Int get() = smsConfig?.chars1Unit ?: 160
    private val charsMultUnit: Int get() = smsConfig?.charsMultUnit ?: 153
    val maxUnits: Int get() = smsConfig?.maxUnits ?: 10

    val smsParts: Int get() = SmsRules.smsParts(message, chars1Unit, charsMultUnit)
    val recipientCount: Int get() = SmsRules.recipientCount(recipients)
    val corporateAvailable: Boolean get() = corporateSenderIds.isNotEmpty()
    val exceedsMaxUnits: Boolean get() = maxUnits > 0 && smsParts > maxUnits

    fun send() {
        error = null
        success = null
        sending = true
        viewModelScope.launch {
            try {
                val senderToUse = if (route == "corporate") corporateSenderId else senderId
                val res = RetrofitClient.apiService.sendSms(senderToUse, recipients, message, route).await()
                success = res.message ?: "SMS sent successfully"
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to send SMS"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                sending = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BulkSmsScreen(navController: NavController) {
    val viewModel: BulkSmsViewModel = viewModel()

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Bulk SMS", onBack = { navController.popBackStack() })

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
        ) {
            // Approved SMS Sender ID
            LabeledDropdown(
                label = "Sender ID (approved only)",
                selected = viewModel.senderId,
                options = viewModel.senderIds.mapNotNull { it.senderId },
                placeholder = "Select an approved Sender ID"
            ) { viewModel.senderId = it }
            if (viewModel.senderIds.isEmpty()) {
                Text(
                    "No approved SMS Sender ID. Register one first.",
                    color = Danger, fontSize = 13.sp,
                    modifier = Modifier.padding(bottom = 12.dp)
                )
            }

            // Route selection
            Text("Route", fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.padding(bottom = 8.dp)) {
                FilterChip(selected = viewModel.route == "promotional", onClick = { viewModel.route = "promotional" }, label = { Text("Promotional") })
                FilterChip(
                    selected = viewModel.route == "corporate",
                    onClick = { if (viewModel.corporateAvailable) viewModel.route = "corporate" },
                    enabled = viewModel.corporateAvailable,
                    label = { Text("Corporate") }
                )
            }
            if (viewModel.route == "corporate") {
                LabeledDropdown(
                    label = "Corporate Sender ID",
                    selected = viewModel.corporateSenderId,
                    options = viewModel.corporateSenderIds.mapNotNull { it.senderId },
                    placeholder = "Select a corporate Sender ID"
                ) { viewModel.corporateSenderId = it }
            } else if (!viewModel.corporateAvailable) {
                Text(
                    "Corporate route requires an approved corporate Sender ID (available on the website).",
                    color = TextSecondary, fontSize = 12.sp,
                    modifier = Modifier.padding(bottom = 16.dp)
                )
            }

            // Recipients with counter
            FintechInput("Recipients", viewModel.recipients, { viewModel.recipients = it }, placeholder = "Numbers separated by commas, spaces, or new lines")
            Text(
                "Recipients: ${viewModel.recipientCount}",
                fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold,
                modifier = Modifier.align(Alignment.End).padding(bottom = 8.dp)
            )

            // Message with counter
            FintechInput("Message", viewModel.message, { viewModel.message = it })
            Row(Modifier.fillMaxWidth().padding(bottom = 8.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Characters: ${viewModel.message.length} | SMS Parts: ${viewModel.smsParts}", fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold)
            }
            if (viewModel.exceedsMaxUnits) {
                Text(
                    "You have exceeded the maximum of ${viewModel.maxUnits} SMS pages.",
                    color = Danger, fontSize = 13.sp,
                    modifier = Modifier.padding(bottom = 8.dp)
                )
            }

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
            if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

            FintechButton(
                if (viewModel.sending) "Sending..." else "Send SMS",
                { viewModel.send() },
                backgroundColor = SmsColor
            )

            TextButton(onClick = { navController.navigate("sender_id") }, modifier = Modifier.fillMaxWidth()) {
                Text("Register a new Sender ID", color = Primary, fontWeight = FontWeight.Bold)
            }
            Spacer(Modifier.height(24.dp))
        }
    }
}

